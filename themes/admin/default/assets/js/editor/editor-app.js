/**
 * Chamy Editor – Main Application Entry Point
 *
 * Modular visual content editor for Chamy CMS.
 * Orchestrates: State Engine, Catalog, Canvas, Inspector, Drag & Drop, API.
 */
(function () {
    'use strict';

    // ─── Configuration ───
    const editorEl = document.getElementById('chamy-editor');
    if (!editorEl) return;

    const CONFIG = {
        contentId: parseInt(editorEl.dataset.contentId, 10),
        contentType: editorEl.dataset.contentType,
        contentStatus: editorEl.dataset.contentStatus,
        contentTitle: editorEl.dataset.contentTitle,
        csrfToken: editorEl.dataset.csrfToken,
        apiBase: editorEl.dataset.apiBase || '/api/v1/editor',
        historyLimit: 50,
        debounceMs: 300,
    };

    // ─── Nesting Constraints ───
    // Defines which child types each parent type can accept.
    // Root accepts everything; blocks/snippets are leaf nodes.
    const NESTING_RULES = {
        root:      ['layout', 'block', 'component', 'snippet'],
        layout:    ['layout', 'block', 'component', 'snippet'],
        component: [],  // overridden per-definition via allowedChildren
        block:     [],
        snippet:   [],
    };

    // beforeunload guard
    window.addEventListener('beforeunload', (e) => {
        if (StateEngine.state.meta.dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ════════════════════════════════════════════════════════════
    //  API Module
    // ════════════════════════════════════════════════════════════
    const API = {
        async request(method, url, body = null) {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            };
            if (body) opts.body = JSON.stringify(body);

            const resp = await fetch(url, opts);
            const data = await resp.json();

            if (!resp.ok) {
                throw { status: resp.status, ...data };
            }
            return data;
        },

        load() {
            return this.request('GET', `${CONFIG.apiBase}/${CONFIG.contentId}`);
        },

        save(editorData, comment = '') {
            return this.request('PUT', `${CONFIG.apiBase}/${CONFIG.contentId}`, {
                editor: editorData,
                comment,
            });
        },

        changeState(newState) {
            return this.request('POST', `${CONFIG.apiBase}/${CONFIG.contentId}/state`, {
                newState,
            });
        },

        preview(editorData) {
            return this.request('POST', `${CONFIG.apiBase}/${CONFIG.contentId}/preview`, {
                editor: editorData,
            });
        },

        loadDefinitions() {
            return this.request('GET', `${CONFIG.apiBase}/definitions`);
        },

        saveComponent(name, node, category) {
            return this.request('POST', `${CONFIG.apiBase}/components/save`, { name, node, category });
        },

        listGlobals() {
            return this.request('GET', `${CONFIG.apiBase}/globals`);
        },

        saveGlobal(name, node, referenceId) {
            return this.request('POST', `${CONFIG.apiBase}/globals/save`, { name, node, referenceId });
        },

        loadGlobal(referenceId) {
            return this.request('GET', `${CONFIG.apiBase}/globals/${encodeURIComponent(referenceId)}`);
        },

        searchContent(type, query) {
            const params = new URLSearchParams();
            if (type) params.set('type', type);
            if (query) params.set('q', query);
            return this.request('GET', `${CONFIG.apiBase}/content-search?${params.toString()}`);
        },
    };

    // ════════════════════════════════════════════════════════════
    //  ID Generator
    // ════════════════════════════════════════════════════════════
    let idCounter = Date.now();
    function generateId(prefix = 'node') {
        return `${prefix}_${(++idCounter).toString(36)}`;
    }

    // Ensure every node in a loaded tree has a stable `id` string.
    // Some persisted/legacy editor payloads may omit `id` fields.
    function ensureNodeIds(node) {
        if (!node) return;
        if (!node.id) node.id = generateId(node.type || 'node');
        if (node.children && Array.isArray(node.children)) {
            node.children.forEach(child => ensureNodeIds(child));
        }
    }

    // ════════════════════════════════════════════════════════════
    //  State Engine
    // ════════════════════════════════════════════════════════════
    const StateEngine = {
        state: {
            document: {
                root: { id: 'root_1', type: 'root', children: [] },
            },
            selection: {
                activeNodeId: null,
                path: [],
            },
            ui: {
                leftSidebarOpen: true,
                rightSidebarOpen: true,
                activeCatalogTab: 'layouts',
                activeInspectorTab: 'content',
                previewMode: 'desktop',
                isDragging: false,
            },
            history: {
                past: [],
                future: [],
            },
            meta: {
                contentId: CONFIG.contentId,
                contentType: CONFIG.contentType,
                dirty: false,
                locked: false,
                lastSave: null,
                editorVersion: 1,
            },
        },

        listeners: [],

        subscribe(fn) {
            this.listeners.push(fn);
            return () => {
                this.listeners = this.listeners.filter(l => l !== fn);
            };
        },

        notify(action) {
            this.listeners.forEach(fn => fn(this.state, action));
        },

        // ── Document mutations ──

        pushHistory() {
            const snapshot = JSON.stringify(this.state.document);
            this.state.history.past.push(snapshot);
            if (this.state.history.past.length > CONFIG.historyLimit) {
                this.state.history.past.shift();
            }
            this.state.history.future = [];
        },

        dispatch(action, payload) {
            switch (action) {
                case 'SET_DOCUMENT':
                    this.state.document = payload;
                    this.state.meta.dirty = false;
                    break;

                case 'ADD_NODE': {
                    this.pushHistory();
                    const { parentId, node, index } = payload;
                    const parent = this.findNode(parentId);
                    if (parent) {
                        if (!parent.children) parent.children = [];
                        if (index !== undefined && index >= 0) {
                            parent.children.splice(index, 0, node);
                        } else {
                            parent.children.push(node);
                        }
                        this.state.meta.dirty = true;
                    }
                    break;
                }

                case 'REMOVE_NODE': {
                    const { nodeId } = payload;
                    const removeTarget = this.findNode(nodeId);
                    if (removeTarget?.meta?.locked) break;
                    this.pushHistory();
                    this.removeNodeById(nodeId);
                    if (this.state.selection.activeNodeId === nodeId) {
                        this.state.selection.activeNodeId = null;
                        this.state.selection.path = [];
                    }
                    this.state.meta.dirty = true;
                    break;
                }

                case 'MOVE_NODE': {
                    const { nodeId: moveId, newParentId, newIndex } = payload;
                    const movingNode = this.findNode(moveId);
                    if (!movingNode) break;
                    if (movingNode.meta?.locked) break;
                    this.pushHistory();
                    const nodeCopy = JSON.parse(JSON.stringify(movingNode));
                    this.removeNodeById(moveId);
                    const newParent = this.findNode(newParentId);
                    if (newParent) {
                        if (!newParent.children) newParent.children = [];
                        if (newIndex !== undefined && newIndex >= 0) {
                            newParent.children.splice(newIndex, 0, nodeCopy);
                        } else {
                            newParent.children.push(nodeCopy);
                        }
                    }
                    this.state.meta.dirty = true;
                    break;
                }

                case 'UPDATE_PROPS': {
                    this.pushHistory();
                    const { nodeId: propNodeId, props, meta: metaUpdate } = payload;
                    const propNode = this.findNode(propNodeId);
                    if (propNode) {
                        propNode.props = { ...(propNode.props || {}), ...props };
                        if (metaUpdate !== undefined) {
                            propNode.meta = metaUpdate;
                        }
                        this.state.meta.dirty = true;
                    }
                    break;
                }

                case 'DUPLICATE_NODE': {
                    this.pushHistory();
                    const { nodeId: dupId } = payload;
                    const dupNode = this.findNode(dupId);
                    if (!dupNode) break;
                    const parentInfo = this.findParent(dupId);
                    if (!parentInfo) break;
                    const clone = this.cloneNode(dupNode);
                    const idx = parentInfo.parent.children.indexOf(dupNode);
                    parentInfo.parent.children.splice(idx + 1, 0, clone);
                    this.state.meta.dirty = true;
                    break;
                }

                case 'SELECT_NODE': {
                    const { nodeId: selId } = payload;
                    this.state.selection.activeNodeId = selId;
                    this.state.selection.path = selId ? this.buildPath(selId) : [];
                    break;
                }

                case 'DESELECT':
                    this.state.selection.activeNodeId = null;
                    this.state.selection.path = [];
                    break;

                case 'TOGGLE_LEFT_SIDEBAR':
                    this.state.ui.leftSidebarOpen = !this.state.ui.leftSidebarOpen;
                    break;

                case 'TOGGLE_RIGHT_SIDEBAR':
                    this.state.ui.rightSidebarOpen = !this.state.ui.rightSidebarOpen;
                    break;

                case 'SET_CATALOG_TAB':
                    this.state.ui.activeCatalogTab = payload.tab;
                    break;

                case 'SET_INSPECTOR_TAB':
                    this.state.ui.activeInspectorTab = payload.tab;
                    break;

                case 'SET_PREVIEW_MODE':
                    this.state.ui.previewMode = payload.mode;
                    break;

                case 'SET_DRAGGING':
                    this.state.ui.isDragging = payload.dragging;
                    break;

                case 'UNDO': {
                    if (this.state.history.past.length === 0) break;
                    const currentSnapshot = JSON.stringify(this.state.document);
                    this.state.history.future.push(currentSnapshot);
                    const prevSnapshot = this.state.history.past.pop();
                    this.state.document = JSON.parse(prevSnapshot);
                    this.state.meta.dirty = true;
                    break;
                }

                case 'REDO': {
                    if (this.state.history.future.length === 0) break;
                    const currentDoc = JSON.stringify(this.state.document);
                    this.state.history.past.push(currentDoc);
                    const nextSnapshot = this.state.history.future.pop();
                    this.state.document = JSON.parse(nextSnapshot);
                    this.state.meta.dirty = true;
                    break;
                }

                case 'MARK_SAVED':
                    this.state.meta.dirty = false;
                    this.state.meta.lastSave = new Date().toISOString();
                    break;
            }

            this.notify(action);
        },

        // ── Tree helpers ──

        findNode(id, node = null) {
            node = node || this.state.document.root;
            if (node.id === id) return node;
            if (node.children) {
                for (const child of node.children) {
                    const found = this.findNode(id, child);
                    if (found) return found;
                }
            }
            return null;
        },

        findParent(id, node = null, parent = null) {
            node = node || this.state.document.root;
            if (node.id === id) return parent ? { parent, index: parent.children.indexOf(node) } : null;
            if (node.children) {
                for (const child of node.children) {
                    const found = this.findParent(id, child, node);
                    if (found) return found;
                }
            }
            return null;
        },

        removeNodeById(id, node = null) {
            node = node || this.state.document.root;
            if (node.children) {
                const idx = node.children.findIndex(c => c.id === id);
                if (idx !== -1) {
                    node.children.splice(idx, 1);
                    return true;
                }
                for (const child of node.children) {
                    if (this.removeNodeById(id, child)) return true;
                }
            }
            return false;
        },

        buildPath(id, node = null, path = []) {
            node = node || this.state.document.root;
            path = [...path, node.id];
            if (node.id === id) return path;
            if (node.children) {
                for (const child of node.children) {
                    const result = this.buildPath(id, child, path);
                    if (result) return result;
                }
            }
            return null;
        },

        cloneNode(node) {
            const clone = JSON.parse(JSON.stringify(node));
            const reassignIds = (n) => {
                n.id = generateId(n.type || 'node');
                if (n.children) n.children.forEach(reassignIds);
            };
            reassignIds(clone);
            return clone;
        },

        getSerializableDocument() {
            return {
                version: this.state.meta.editorVersion,
                contentType: this.state.meta.contentType,
                root: this.state.document.root,
            };
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Toast Notifications
    // ════════════════════════════════════════════════════════════
    function showToast(message, type = 'info') {
        const existing = document.querySelector('.editor-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `editor-toast editor-toast--${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    }

    // ════════════════════════════════════════════════════════════
    //  Definitions Registry (loaded from API)
    // ════════════════════════════════════════════════════════════
    let definitions = { layouts: {}, blocks: {}, components: {}, snippets: {} };

    function getDefinition(type, definitionId) {
        const typeMap = {
            layout: 'layouts',
            block: 'blocks',
            component: 'components',
            snippet: 'snippets',
        };
        const key = typeMap[type] || type;
        return (definitions[key] || {})[definitionId] || null;
    }

    function getAllDefinitions(type) {
        return definitions[type] || {};
    }

    // ════════════════════════════════════════════════════════════
    //  Catalog Module
    // ════════════════════════════════════════════════════════════
    const Catalog = {
        init() {
            this.renderAll();
            this.bindTabs();
            this.bindSearch();
        },

        renderAll() {
            this.renderPanel('layouts', definitions.layouts);
            this.renderPanel('blocks', definitions.blocks);
            this.renderPanel('components', definitions.components);
            this.renderPanel('snippets', definitions.snippets);
        },

        renderPanel(type, items) {
            const panel = document.querySelector(`[data-catalog-panel="${type}"]`);
            if (!panel) return;

            // Group by category
            const categories = {};
            for (const [id, def] of Object.entries(items)) {
                const cat = def.category || 'general';
                if (!categories[cat]) categories[cat] = [];
                categories[cat].push(def);
            }

            let html = '';
            for (const [cat, defs] of Object.entries(categories)) {
                html += `<div class="editor-catalog-category">${this.escapeCategoryLabel(cat)}</div>`;
                for (const def of defs) {
                    html += this.renderItem(def);
                }
            }

            panel.innerHTML = html || '<p style="padding:10px;color:var(--text-muted);font-size:11px;">Keine Elemente verfügbar</p>';

            // Bind drag events
            panel.querySelectorAll('.editor-catalog-item').forEach(item => {
                item.addEventListener('dragstart', (e) => this.onDragStart(e));
                item.addEventListener('dragend', (e) => this.onDragEnd(e));
            });
        },

        renderItem(def) {
            const icon = this.getIconSvg(def.icon || def.type);
            return `
                <div class="editor-catalog-item" draggable="true"
                     data-def-id="${def.id}" data-def-type="${def.type}">
                    <div class="editor-catalog-item__icon">${icon}</div>
                    <div class="editor-catalog-item__info">
                        <div class="editor-catalog-item__name">${this.escapeHtml(def.label)}</div>
                        <div class="editor-catalog-item__desc">${this.escapeHtml(def.description || '')}</div>
                    </div>
                </div>
            `;
        },

        getIconSvg(icon) {
            const icons = {
                layout: '⊞',
                section: '▬',
                container: '☐',
                grid: '⊞',
                columns: '‖',
                text: 'T',
                heading: 'H',
                image: '🖼',
                video: '▶',
                button: '⬜',
                spacer: '↕',
                divider: '—',
                hero: '⬛',
                cta: '➤',
                faq: '?',
                component: '◉',
                snippet: '✦',
                info: 'ℹ',
                notice: '⚠',
                contact: '✉',
            };
            return icons[icon] || '●';
        },

        onDragStart(e) {
            const item = e.target.closest('.editor-catalog-item');
            if (!item) return;

            const defId = item.dataset.defId;
            const defType = item.dataset.defType;

            e.dataTransfer.setData('application/chamy-editor', JSON.stringify({
                source: 'catalog',
                definitionId: defId,
                type: defType,
            }));
            e.dataTransfer.effectAllowed = 'copy';

            StateEngine.dispatch('SET_DRAGGING', { dragging: true });

            // Create drag ghost
            const ghost = document.createElement('div');
            ghost.className = 'editor-drag-ghost';
            ghost.textContent = item.querySelector('.editor-catalog-item__name').textContent;
            ghost.id = 'drag-ghost';
            document.body.appendChild(ghost);
            e.dataTransfer.setDragImage(ghost, 0, 0);
        },

        onDragEnd(e) {
            StateEngine.dispatch('SET_DRAGGING', { dragging: false });
            const ghost = document.getElementById('drag-ghost');
            if (ghost) ghost.remove();
        },

        bindTabs() {
            document.querySelectorAll('.editor-catalog-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.catalogTab;
                    StateEngine.dispatch('SET_CATALOG_TAB', { tab: tabName });

                    document.querySelectorAll('.editor-catalog-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    document.querySelectorAll('.editor-catalog-panel').forEach(p => p.classList.remove('active'));
                    document.querySelector(`[data-catalog-panel="${tabName}"]`)?.classList.add('active');
                });
            });
        },

        bindSearch() {
            const input = document.getElementById('catalog-search');
            if (!input) return;

            let debounce;
            input.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => this.filterItems(input.value), 200);
            });
        },

        filterItems(query) {
            const q = query.toLowerCase().trim();
            document.querySelectorAll('.editor-catalog-item').forEach(item => {
                const name = item.querySelector('.editor-catalog-item__name')?.textContent?.toLowerCase() || '';
                const desc = item.querySelector('.editor-catalog-item__desc')?.textContent?.toLowerCase() || '';
                item.style.display = (!q || name.includes(q) || desc.includes(q)) ? '' : 'none';
            });
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        escapeCategoryLabel(cat) {
            const labels = {
                layout: 'Layout',
                text: 'Text',
                media: 'Medien',
                ui: 'UI-Elemente',
                marketing: 'Marketing',
                content: 'Inhalt',
                general: 'Allgemein',
                navigation: 'Navigation',
            };
            return labels[cat] || this.escapeHtml(cat);
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Canvas Renderer
    // ════════════════════════════════════════════════════════════
    const Canvas = {
        canvasEl: null,
        canvasInner: null,
        emptyEl: null,

        init() {
            this.canvasEl = document.getElementById('editor-canvas');
            this.canvasInner = document.getElementById('editor-canvas-inner');
            this.emptyEl = document.getElementById('editor-canvas-empty');
        },

        render() {
            const root = StateEngine.state.document.root;
            const hasChildren = root.children && root.children.length > 0;

            if (this.emptyEl) {
                this.emptyEl.style.display = hasChildren ? 'none' : '';
            }

            // Remove old rendered nodes but keep empty element
            this.canvasInner.querySelectorAll('.editor-node, .editor-dropzone').forEach(el => el.remove());

            if (hasChildren) {
                const fragment = document.createDocumentFragment();
                // Top drop zone
                fragment.appendChild(this.createDropZone('root_1', 0));
                root.children.forEach((child, idx) => {
                    fragment.appendChild(this.renderNode(child));
                    fragment.appendChild(this.createDropZone('root_1', idx + 1));
                });
                this.canvasInner.appendChild(fragment);
            } else {
                // Root drop zone for empty canvas
                const dz = this.createDropZone('root_1', 0);
                dz.classList.add('active');
                dz.dataset.label = 'Element hier ablegen';
                this.canvasInner.appendChild(dz);
            }
        },

        renderNode(node) {
            const el = document.createElement('div');
            el.className = `editor-node editor-node--${node.type}`;
            el.dataset.nodeId = node.id;
            el.dataset.nodeType = node.type;
            el.dataset.nodeDef = node.definition || '';

            const isLocked = node.meta?.locked === true;
            const allowContentEditing = node.meta?.allowContentEditing === true;
            const isGlobal = node.meta?.global === true;

            if (isLocked) {
                el.classList.add('editor-node--locked');
            }
            if (isGlobal) {
                el.classList.add('editor-node--global');
            }

            if (node.id === StateEngine.state.selection.activeNodeId) {
                el.classList.add('selected');
            }

            // Apply a lightweight live-preview style mapping so layout structures
            // (grid/columns/container) are visible in the canvas itself.
            this.applyLiveNodeStyles(el, node);

            // Label
            const label = document.createElement('div');
            label.className = 'editor-node__label';
            label.textContent = node.label || node.definition || node.type;
            if (isLocked) {
                label.innerHTML = '<svg class="editor-lock-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> ' + this.escapeHtml(node.label || node.definition || node.type);
            }
            el.appendChild(label);

            // Actions (hide destructive actions for locked nodes)
            el.appendChild(this.createNodeActions(node));

            // Content rendering
            const content = this.renderNodeContent(node);
            if (content) el.appendChild(content);

            // Children (for layout/root/component with children)
            if (node.children && node.children.length > 0) {
                const childContainer = document.createElement('div');
                childContainer.className = 'editor-node__children';

                // Apply live child layout (e.g. grid/columns) to the children wrapper.
                this.applyLiveChildrenLayout(childContainer, node);

                // Inner drop zone at top
                childContainer.appendChild(this.createDropZone(node.id, 0));

                node.children.forEach((child, idx) => {
                    childContainer.appendChild(this.renderNode(child));
                    childContainer.appendChild(this.createDropZone(node.id, idx + 1));
                });

                el.appendChild(childContainer);
            } else if (node.type === 'layout' || (node.type === 'component' && this.canHaveChildren(node))) {
                // Empty container drop zone
                const emptyDz = this.createDropZone(node.id, 0);
                emptyDz.classList.add('active');
                emptyDz.dataset.label = 'Element einfügen';
                emptyDz.style.minHeight = '60px';
                el.appendChild(emptyDz);
            }

            // Click selection
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                StateEngine.dispatch('SELECT_NODE', { nodeId: node.id });
            });

            // Context menu
            el.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                e.stopPropagation();
                ContextMenu.show(e.clientX, e.clientY, node);
            });

            // Make draggable for reorder (unless locked)
            el.draggable = !isLocked;
            if (!isLocked) {
                el.addEventListener('dragstart', (e) => {
                    e.stopPropagation();
                    e.dataTransfer.setData('application/chamy-editor', JSON.stringify({
                        source: 'canvas',
                        nodeId: node.id,
                    }));
                    e.dataTransfer.effectAllowed = 'move';
                    StateEngine.dispatch('SET_DRAGGING', { dragging: true });
                });
                el.addEventListener('dragend', () => {
                    StateEngine.dispatch('SET_DRAGGING', { dragging: false });
                });
            }

            return el;
        },

        renderNodeContent(node) {
            const props = node.props || {};
            const div = document.createElement('div');
            div.className = 'editor-node__content';

            switch (node.type) {
                case 'block':
                    return this.renderBlockContent(node.definition, props, div);
                case 'component':
                    div.innerHTML = `<div style="padding:8px;color:var(--text-muted);font-size:11px;text-align:center;">
                        ${this.escapeHtml(node.label || node.definition || 'Komponente')}
                    </div>`;
                    return div;
                case 'snippet':
                    div.innerHTML = `<div style="padding:6px;border-left:3px solid var(--neon-green);font-size:11px;color:var(--text-secondary);">
                        ${this.escapeHtml(props.title || props.text || node.label || 'Snippet')}
                    </div>`;
                    return div;
                default:
                    return null;
            }
        },

        renderBlockContent(definition, props, div) {
            switch (definition) {
                case 'text':
                    div.innerHTML = `<div style="font-size:13px;color:var(--text-primary);min-height:20px;">${props.content || '<em style="color:var(--text-muted)">Text eingeben...</em>'}</div>`;
                    return div;
                case 'heading': {
                    const level = props.level || 'h2';
                    const sizes = { h1: '24px', h2: '20px', h3: '17px', h4: '15px' };
                    div.innerHTML = `<div style="font-size:${sizes[level] || '20px'};font-weight:700;color:var(--text-primary);">${this.escapeHtml(props.text || 'Überschrift')}</div>`;
                    return div;
                }
                case 'image':
                    if (props.src) {
                        div.innerHTML = `<img src="${this.escapeHtml(props.src)}" alt="${this.escapeHtml(props.alt || '')}" style="max-width:100%;border-radius:var(--radius-sm);">`;
                    } else {
                        div.innerHTML = `<div style="padding:20px;text-align:center;background:var(--surface-200);border-radius:var(--radius-sm);color:var(--text-muted);font-size:12px;">🖼 Bild auswählen</div>`;
                    }
                    return div;
                case 'button':
                    div.innerHTML = `<div style="text-align:center;padding:4px;"><span style="display:inline-block;padding:6px 16px;border-radius:var(--radius-sm);background:var(--accent);color:var(--surface-000);font-size:12px;font-weight:600;">${this.escapeHtml(props.label || 'Button')}</span></div>`;
                    return div;
                case 'spacer':
                    const heights = { sm: '16px', md: '32px', lg: '64px', xl: '96px' };
                    div.innerHTML = `<div style="height:${heights[props.height] || '32px'};background:repeating-linear-gradient(45deg,transparent,transparent 4px,var(--surface-200) 4px,var(--surface-200) 8px);border-radius:var(--radius-sm);opacity:0.3;"></div>`;
                    return div;
                case 'divider':
                    div.innerHTML = `<hr style="border:none;border-top:1px ${props.style || 'solid'} var(--border-color);margin:8px 0;">`;
                    return div;
                case 'video':
                    div.innerHTML = `<div style="padding:20px;text-align:center;background:var(--surface-200);border-radius:var(--radius-sm);color:var(--text-muted);font-size:12px;">▶ Video${props.url ? ': ' + this.escapeHtml(props.url) : ''}</div>`;
                    return div;
                default:
                    div.innerHTML = `<div style="padding:8px;color:var(--text-muted);font-size:11px;">[${this.escapeHtml(definition)}]</div>`;
                    return div;
            }
        },

        createNodeActions(node) {
            const actions = document.createElement('div');
            actions.className = 'editor-node__actions';

            const isLocked = node.meta?.locked === true;

            // Duplicate
            const dupBtn = document.createElement('button');
            dupBtn.className = 'editor-node__action';
            dupBtn.title = 'Duplizieren';
            dupBtn.innerHTML = '⧉';
            dupBtn.disabled = isLocked;
            dupBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!isLocked) StateEngine.dispatch('DUPLICATE_NODE', { nodeId: node.id });
            });
            actions.appendChild(dupBtn);

            // Move up
            const upBtn = document.createElement('button');
            upBtn.className = 'editor-node__action';
            upBtn.title = 'Nach oben';
            upBtn.innerHTML = '↑';
            upBtn.disabled = isLocked;
            upBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!isLocked) this.moveNodeUp(node.id);
            });
            actions.appendChild(upBtn);

            // Move down
            const downBtn = document.createElement('button');
            downBtn.className = 'editor-node__action';
            downBtn.title = 'Nach unten';
            downBtn.innerHTML = '↓';
            downBtn.disabled = isLocked;
            downBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!isLocked) this.moveNodeDown(node.id);
            });
            actions.appendChild(downBtn);

            // Delete
            const delBtn = document.createElement('button');
            delBtn.className = 'editor-node__action editor-node__action--danger';
            delBtn.title = isLocked ? 'Layout geschützt' : 'Löschen';
            delBtn.innerHTML = '✕';
            delBtn.disabled = isLocked;
            delBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!isLocked) StateEngine.dispatch('REMOVE_NODE', { nodeId: node.id });
            });
            actions.appendChild(delBtn);

            return actions;
        },

        moveNodeUp(nodeId) {
            const parentInfo = StateEngine.findParent(nodeId);
            if (!parentInfo || parentInfo.index <= 0) return;
            StateEngine.dispatch('MOVE_NODE', {
                nodeId,
                newParentId: parentInfo.parent.id,
                newIndex: parentInfo.index - 1,
            });
        },

        moveNodeDown(nodeId) {
            const parentInfo = StateEngine.findParent(nodeId);
            if (!parentInfo || parentInfo.index >= parentInfo.parent.children.length - 1) return;
            StateEngine.dispatch('MOVE_NODE', {
                nodeId,
                newParentId: parentInfo.parent.id,
                newIndex: parentInfo.index + 1,
            });
        },

        createDropZone(parentId, index) {
            const dz = document.createElement('div');
            dz.className = 'editor-dropzone';
            dz.dataset.parentId = parentId;
            dz.dataset.index = index;

            dz.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Check constraint during drag
                const raw = e.dataTransfer.types.includes('application/chamy-editor');
                if (raw) {
                    e.dataTransfer.dropEffect = 'copy';
                    dz.classList.add('drag-over');
                }
            });

            dz.addEventListener('dragleave', (e) => {
                dz.classList.remove('drag-over');
            });

            dz.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dz.classList.remove('drag-over');

                const raw = e.dataTransfer.getData('application/chamy-editor');
                if (!raw) return;

                try {
                    const data = JSON.parse(raw);
                    DragDrop.handleDrop(data, parentId, parseInt(dz.dataset.index, 10));
                } catch (err) {
                    console.error('Drop parse error:', err);
                }
            });

            return dz;
        },

        canHaveChildren(node) {
            const def = getDefinition(node.type, node.definition);
            return def && def.allowedChildren && def.allowedChildren.length > 0;
        },

        applyLiveNodeStyles(el, node) {
            const props = node.props || {};

            // Generic spacing helpers shared by all node types.
            const padMap = { none: '0', sm: '8px', md: '16px', lg: '24px', xl: '32px' };
            const marginMap = { none: '0', sm: '8px', md: '16px', lg: '24px', xl: '32px' };
            const radiusMap = { none: '0', sm: '4px', md: '8px', lg: '12px', full: '999px' };

            if (props._padding && padMap[props._padding] !== undefined) {
                el.style.padding = padMap[props._padding];
            }
            if (props._margin && marginMap[props._margin] !== undefined) {
                el.style.margin = `${marginMap[props._margin]} 0`;
            }
            if (props._borderRadius && radiusMap[props._borderRadius] !== undefined) {
                el.style.borderRadius = radiusMap[props._borderRadius];
            }

            // Layout-specific live rendering.
            if (node.type === 'layout') {
                const defId = node.definition || '';
                if (defId === 'container') {
                    const widthMap = { sm: '640px', md: '960px', lg: '1200px', full: '100%' };
                    const maxWidth = widthMap[props.maxWidth || 'lg'] || '1200px';
                    el.style.maxWidth = maxWidth;
                    el.style.marginLeft = 'auto';
                    el.style.marginRight = 'auto';
                    el.style.width = '100%';
                }

                if (defId === 'section') {
                    const bgMap = {
                        none: 'transparent',
                        light: 'rgba(255,255,255,0.04)',
                        dark: 'rgba(0,0,0,0.16)',
                        accent: 'rgba(108, 92, 231, 0.12)',
                    };
                    const pMap = { none: '0', sm: '12px', md: '24px', lg: '36px' };
                    el.style.background = bgMap[props.background || 'none'] || 'transparent';
                    el.style.padding = pMap[props.padding || 'md'] || '24px';
                }
            }
        },

        applyLiveChildrenLayout(childContainer, node) {
            const props = node.props || {};

            // Core layout: grid
            if (node.type === 'layout' && node.definition === 'grid') {
                const cols = Math.max(1, parseInt(props.columns || '2', 10) || 2);
                const gapMap = { none: '0', sm: '10px', md: '16px', lg: '24px' };
                childContainer.style.display = 'grid';
                childContainer.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
                childContainer.style.gap = gapMap[props.gap || 'md'] || '16px';
                childContainer.classList.add('editor-node__children--live-grid');
                return;
            }

            // Core layout: columns (ratio based)
            if (node.type === 'layout' && node.definition === 'columns') {
                const ratioMap = {
                    '1:1': '1fr 1fr',
                    '1:2': '1fr 2fr',
                    '2:1': '2fr 1fr',
                    '1:1:1': '1fr 1fr 1fr',
                };
                childContainer.style.display = 'grid';
                childContainer.style.gridTemplateColumns = ratioMap[props.ratio || '1:1'] || '1fr 1fr';
                childContainer.style.gap = '16px';
                childContainer.classList.add('editor-node__children--live-grid');
                return;
            }

            // Component layout: feature grid with horizontal cards.
            if (node.type === 'component' && node.definition === 'feature_grid') {
                const cols = Math.max(1, parseInt(props.columns || '3', 10) || 3);
                childContainer.style.display = 'grid';
                childContainer.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
                childContainer.style.gap = '14px';
                childContainer.classList.add('editor-node__children--live-grid');
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Drag & Drop Module
    // ════════════════════════════════════════════════════════════
    const DragDrop = {
        handleDrop(data, parentId, index) {
            if (data.source === 'catalog') {
                this.dropFromCatalog(data, parentId, index);
            } else if (data.source === 'canvas') {
                this.dropFromCanvas(data, parentId, index);
            }
        },

        /**
         * Check if a child type is allowed inside a given parent node.
         */
        canAccept(parentId, childType, childDefinition) {
            const parent = StateEngine.findNode(parentId);

            // Root accepts based on NESTING_RULES
            if (!parent || parent.type === 'root') {
                return NESTING_RULES.root.includes(childType);
            }

            // Check definition-level allowedChildren first
            const parentDef = getDefinition(parent.type, parent.definition);
            if (parentDef && parentDef.allowedChildren && parentDef.allowedChildren.length > 0) {
                return parentDef.allowedChildren.includes(childType);
            }

            // Fallback to type-level rules
            const allowed = NESTING_RULES[parent.type] || [];
            return allowed.includes(childType);
        },

        dropFromCatalog(data, parentId, index) {
            const def = getDefinition(data.type, data.definitionId);
            if (!def) {
                showToast('Definition nicht gefunden', 'error');
                return;
            }

            // Validate nesting constraint
            if (!this.canAccept(parentId, data.type, data.definitionId)) {
                showToast('Dieses Element kann hier nicht eingefügt werden', 'error');
                return;
            }

            // Create new node instance
            const node = {
                id: generateId(data.type),
                type: data.type,
                definition: data.definitionId,
                source: def.source || 'core',
                label: def.label,
                props: { ...(def.defaultProps || {}) },
                children: [],
                meta: {
                    templateId: data.definitionId,
                    createdFrom: 'component_catalog',
                    resettable: true,
                },
            };

            StateEngine.dispatch('ADD_NODE', { parentId, node, index });
        },

        dropFromCanvas(data, parentId, index) {
            // Prevent dropping into own descendants
            const path = StateEngine.buildPath(parentId);
            if (path && path.includes(data.nodeId)) {
                showToast('Element kann nicht in sich selbst verschoben werden', 'error');
                return;
            }

            // Validate nesting constraint for the moved node
            const node = StateEngine.findNode(data.nodeId);
            if (node && !this.canAccept(parentId, node.type, node.definition)) {
                showToast('Dieses Element kann hier nicht platziert werden', 'error');
                return;
            }

            StateEngine.dispatch('MOVE_NODE', {
                nodeId: data.nodeId,
                newParentId: parentId,
                newIndex: index,
            });
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Inspector Module
    // ════════════════════════════════════════════════════════════
    const Inspector = {
        contentPanel: null,
        layoutPanel: null,
        designPanel: null,
        advancedPanel: null,

        init() {
            this.contentPanel = document.querySelector('[data-inspector-panel="content"]');
            this.layoutPanel = document.querySelector('[data-inspector-panel="layout"]');
            this.designPanel = document.querySelector('[data-inspector-panel="design"]');
            this.advancedPanel = document.querySelector('[data-inspector-panel="advanced"]');
            this.bindTabs();
        },

        bindTabs() {
            document.querySelectorAll('.editor-inspector-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.inspectorTab;
                    StateEngine.dispatch('SET_INSPECTOR_TAB', { tab: tabName });

                    document.querySelectorAll('.editor-inspector-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    document.querySelectorAll('.editor-inspector-panel').forEach(p => p.classList.remove('active'));
                    document.querySelector(`[data-inspector-panel="${tabName}"]`)?.classList.add('active');
                });
            });
        },

        update() {
            const nodeId = StateEngine.state.selection.activeNodeId;
            if (!nodeId) {
                this.showEmpty();
                return;
            }

            const node = StateEngine.findNode(nodeId);
            if (!node) {
                this.showEmpty();
                return;
            }

            // Auto-open right sidebar when selecting an element
            if (!StateEngine.state.ui.rightSidebarOpen) {
                StateEngine.dispatch('TOGGLE_RIGHT_SIDEBAR');
            }

            document.getElementById('inspector-title').textContent = node.label || node.definition || 'Eigenschaften';

            const isLocked = node.meta?.locked === true;
            const allowContentEditing = node.meta?.allowContentEditing === true;

            if (isLocked && !allowContentEditing) {
                // Fully locked – show info only
                const lockedHtml = '<div class="editor-inspector-empty"><p>Dieses Element ist geschützt und kann nicht bearbeitet werden.</p></div>';
                if (this.contentPanel) this.contentPanel.innerHTML = lockedHtml;
                if (this.layoutPanel) this.layoutPanel.innerHTML = '';
                if (this.designPanel) this.designPanel.innerHTML = '';
                if (this.advancedPanel) this.advancedPanel.innerHTML = '';
                return;
            }

            this.renderFields(node);

            if (isLocked) {
                // Locked with content editing only – hide layout/design tabs
                if (this.layoutPanel) this.layoutPanel.innerHTML = '';
                if (this.designPanel) this.designPanel.innerHTML = '';
            } else {
                this.renderLayoutTab(node);
                this.renderDesignTab(node);
            }
            this.renderAdvancedTab(node);
        },

        showEmpty() {
            const emptyHtml = `<div class="editor-inspector-empty"><p>Wähle ein Element aus, um es zu bearbeiten</p></div>`;
            if (this.contentPanel) this.contentPanel.innerHTML = emptyHtml;
            if (this.layoutPanel) this.layoutPanel.innerHTML = '';
            if (this.designPanel) this.designPanel.innerHTML = '';
            if (this.advancedPanel) this.advancedPanel.innerHTML = '';
            document.getElementById('inspector-title').textContent = 'Eigenschaften';
        },

        renderFields(node) {
            if (!this.contentPanel) return;

            const def = getDefinition(node.type, node.definition);
            const fields = def?.fields || [];
            const props = node.props || {};

            if (fields.length === 0) {
                this.contentPanel.innerHTML = `
                    <div class="editor-inspector-empty">
                        <p>Keine bearbeitbaren Eigenschaften</p>
                    </div>
                `;
                return;
            }

            let html = '';
            for (const field of fields) {
                html += this.renderField(field, props[field.name], node.id);
            }

            this.contentPanel.innerHTML = html;

            // Bind field change events
            this.contentPanel.querySelectorAll('[data-field-name]').forEach(input => {
                const handler = () => {
                    const fieldName = input.dataset.fieldName;
                    let value;
                    if (input.classList.contains('editor-field__toggle')) {
                        value = input.classList.toggle('active');
                    } else {
                        value = input.value;
                    }
                    StateEngine.dispatch('UPDATE_PROPS', {
                        nodeId: node.id,
                        props: { [fieldName]: value },
                    });
                };

                if (input.tagName === 'SELECT' || input.classList.contains('editor-field__toggle')) {
                    input.addEventListener('change', handler);
                    if (input.classList.contains('editor-field__toggle')) {
                        input.addEventListener('click', handler);
                    }
                } else {
                    let debounceTimer;
                    input.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(handler, CONFIG.debounceMs);
                    });
                }
            });

            // Bind reference search fields
            this.bindReferenceFields(this.contentPanel, node.id);
        },

        renderField(field, value, nodeId) {
            const name = this.escapeHtml(field.name);
            const label = this.escapeHtml(field.label || field.name);
            const required = field.required ? ' <span style="color:var(--neon-magenta);">*</span>' : '';

            switch (field.type) {
                case 'text':
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}${required}</label>
                        <input class="editor-field__input" type="text" data-field-name="${name}" value="${this.escapeAttr(value || '')}">
                    </div>`;

                case 'textarea':
                case 'richtext':
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}${required}</label>
                        <textarea class="editor-field__textarea" data-field-name="${name}">${this.escapeHtml(value || '')}</textarea>
                    </div>`;

                case 'select':
                    const options = field.options || {};
                    let optHtml = '';
                    for (const [optVal, optLabel] of Object.entries(options)) {
                        const selected = value === optVal ? ' selected' : '';
                        optHtml += `<option value="${this.escapeAttr(optVal)}"${selected}>${this.escapeHtml(optLabel)}</option>`;
                    }
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}</label>
                        <select class="editor-field__select" data-field-name="${name}">${optHtml}</select>
                    </div>`;

                case 'number':
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}</label>
                        <input class="editor-field__input" type="number" data-field-name="${name}" value="${this.escapeAttr(value || '')}">
                    </div>`;

                case 'toggle':
                case 'checkbox':
                    const activeClass = value ? ' active' : '';
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}</label>
                        <button class="editor-field__toggle${activeClass}" data-field-name="${name}"></button>
                    </div>`;

                case 'media':
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}</label>
                        <input class="editor-field__input" type="text" data-field-name="${name}" value="${this.escapeAttr(value || '')}" placeholder="Bild-URL...">
                    </div>`;

                case 'reference': {
                    const refType = field.referenceType || '';
                    const multiple = field.multiple ? 'true' : 'false';
                    const currentRefs = Array.isArray(value) ? value : (value ? [value] : []);
                    const refLabels = currentRefs.map(r => {
                        if (typeof r === 'object') return `<span class="editor-ref-tag" data-ref-id="${r.id}">${this.escapeHtml(r.title || r.id)} <button class="editor-ref-remove" data-field-name="${name}" data-ref-id="${r.id}">✕</button></span>`;
                        return `<span class="editor-ref-tag" data-ref-id="${r}">#${r} <button class="editor-ref-remove" data-field-name="${name}" data-ref-id="${r}">✕</button></span>`;
                    }).join(' ');
                    return `<div class="editor-field editor-field--reference" data-reference-type="${this.escapeAttr(refType)}" data-multiple="${multiple}">
                        <label class="editor-field__label">${label} <small style="opacity:0.6">🔗</small></label>
                        <div class="editor-ref-tags" data-field-name="${name}">${refLabels}</div>
                        <input class="editor-field__input editor-ref-search" type="text" data-field-name="${name}" data-ref-type="${this.escapeAttr(refType)}" placeholder="Inhalt suchen...">
                        <div class="editor-ref-results" data-field-name="${name}" style="display:none;"></div>
                    </div>`;
                }

                default:
                    return `<div class="editor-field">
                        <label class="editor-field__label">${label}</label>
                        <input class="editor-field__input" type="text" data-field-name="${name}" value="${this.escapeAttr(value || '')}">
                    </div>`;
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = String(text ?? '');
            return div.innerHTML;
        },

        escapeAttr(text) {
            return String(text ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },

        // ── Layout Tab ──
        renderLayoutTab(node) {
            if (!this.layoutPanel) return;

            const layoutFields = [
                { name: '_margin', type: 'select', label: 'Außenabstand', options: { none: 'Kein', sm: 'Klein', md: 'Mittel', lg: 'Groß', xl: 'Sehr groß' } },
                { name: '_padding', type: 'select', label: 'Innenabstand', options: { none: 'Kein', sm: 'Klein', md: 'Mittel', lg: 'Groß', xl: 'Sehr groß' } },
                { name: '_width', type: 'select', label: 'Breite', options: { auto: 'Automatisch', full: 'Volle Breite', '50%': '50%', '75%': '75%' } },
                { name: '_alignment', type: 'select', label: 'Ausrichtung', options: { left: 'Links', center: 'Zentriert', right: 'Rechts' } },
            ];

            const props = node.props || {};
            let html = '<div class="editor-inspector-section-title">Layout-Einstellungen</div>';
            for (const field of layoutFields) {
                html += this.renderField(field, props[field.name], node.id);
            }
            this.layoutPanel.innerHTML = html;
            this.bindPanelFields(this.layoutPanel, node.id);
        },

        // ── Design Tab ──
        renderDesignTab(node) {
            if (!this.designPanel) return;

            const designFields = [
                { name: '_bgColor', type: 'select', label: 'Hintergrundfarbe', options: { none: 'Keine', light: 'Hell', dark: 'Dunkel', accent: 'Akzent', surface: 'Oberfläche' } },
                { name: '_bgImage', type: 'media', label: 'Hintergrundbild' },
                { name: '_borderRadius', type: 'select', label: 'Eckenradius', options: { none: 'Keine', sm: 'Klein', md: 'Mittel', lg: 'Groß', full: 'Rund' } },
                { name: '_shadow', type: 'select', label: 'Schatten', options: { none: 'Kein', sm: 'Leicht', md: 'Mittel', lg: 'Stark' } },
                { name: '_opacity', type: 'select', label: 'Deckkraft', options: { '100': '100%', '75': '75%', '50': '50%', '25': '25%' } },
            ];

            const props = node.props || {};
            let html = '<div class="editor-inspector-section-title">Design-Einstellungen</div>';
            for (const field of designFields) {
                html += this.renderField(field, props[field.name], node.id);
            }
            this.designPanel.innerHTML = html;
            this.bindPanelFields(this.designPanel, node.id);
        },

        // ── Advanced Tab ──
        renderAdvancedTab(node) {
            if (!this.advancedPanel) return;

            const advancedFields = [
                { name: '_cssClass', type: 'text', label: 'CSS-Klassen' },
                { name: '_htmlId', type: 'text', label: 'HTML-ID' },
                { name: '_visibility', type: 'select', label: 'Sichtbarkeit', options: { visible: 'Sichtbar', hidden: 'Versteckt', 'desktop-only': 'Nur Desktop', 'mobile-only': 'Nur Mobil' } },
            ];

            const props = node.props || {};
            let html = '<div class="editor-inspector-section-title">Erweiterte Einstellungen</div>';
            for (const field of advancedFields) {
                html += this.renderField(field, props[field.name], node.id);
            }

            // Node info (read-only)
            html += `<div class="editor-inspector-section-title" style="margin-top:16px;">Element-Info</div>`;
            html += `<div class="editor-field"><label class="editor-field__label">Typ</label><div style="font-size:11px;color:var(--text-secondary);padding:4px 0;">${this.escapeHtml(node.type)}</div></div>`;
            html += `<div class="editor-field"><label class="editor-field__label">Definition</label><div style="font-size:11px;color:var(--text-secondary);padding:4px 0;">${this.escapeHtml(node.definition || '–')}</div></div>`;
            html += `<div class="editor-field"><label class="editor-field__label">Quelle</label><div style="font-size:11px;color:var(--text-secondary);padding:4px 0;">${this.escapeHtml(node.source || 'core')}</div></div>`;
            html += `<div class="editor-field"><label class="editor-field__label">ID</label><div style="font-size:11px;color:var(--text-secondary);padding:4px 0;font-family:monospace;">${this.escapeHtml(node.id)}</div></div>`;

            this.advancedPanel.innerHTML = html;
            this.bindPanelFields(this.advancedPanel, node.id);
        },

        // ── Shared field event binding for any panel ──
        bindPanelFields(panel, nodeId) {
            panel.querySelectorAll('[data-field-name]').forEach(input => {
                const handler = () => {
                    const fieldName = input.dataset.fieldName;
                    let value;
                    if (input.classList.contains('editor-field__toggle')) {
                        value = input.classList.toggle('active');
                    } else {
                        value = input.value;
                    }
                    StateEngine.dispatch('UPDATE_PROPS', {
                        nodeId,
                        props: { [fieldName]: value },
                    });
                };

                if (input.tagName === 'SELECT' || input.classList.contains('editor-field__toggle')) {
                    input.addEventListener('change', handler);
                    if (input.classList.contains('editor-field__toggle')) {
                        input.addEventListener('click', handler);
                    }
                } else {
                    let debounceTimer;
                    input.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(handler, CONFIG.debounceMs);
                    });
                }
            });
        },

        bindReferenceFields(panel, nodeId) {
            panel.querySelectorAll('.editor-ref-search').forEach(input => {
                let debounceTimer;
                const fieldName = input.dataset.fieldName;
                const refType = input.dataset.refType || '';
                const resultsEl = panel.querySelector(`.editor-ref-results[data-field-name="${fieldName}"]`);

                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(async () => {
                        const q = input.value.trim();
                        if (q.length < 2) {
                            if (resultsEl) resultsEl.style.display = 'none';
                            return;
                        }

                        try {
                            const result = await API.searchContent(refType, q);
                            const data = result.data || result;
                            const items = data.results || [];

                            if (!resultsEl) return;
                            resultsEl.style.display = '';
                            resultsEl.innerHTML = items.length === 0
                                ? '<div class="editor-ref-no-results">Keine Ergebnisse</div>'
                                : items.map(item => `<button class="editor-ref-result" data-ref-id="${item.id}" data-ref-title="${Canvas.escapeHtml(item.title)}" data-ref-type="${item.contentType}">${Canvas.escapeHtml(item.title)} <small>(${item.contentType})</small></button>`).join('');

                            resultsEl.querySelectorAll('.editor-ref-result').forEach(btn => {
                                btn.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    const ref = { id: parseInt(btn.dataset.refId), title: btn.dataset.refTitle, contentType: btn.dataset.refType };
                                    const fieldEl = panel.closest('.editor-field--reference');
                                    const isMultiple = fieldEl?.dataset.multiple === 'true';
                                    const node = StateEngine.findNode(nodeId);
                                    const current = node?.props?.[fieldName];

                                    let newValue;
                                    if (isMultiple) {
                                        const arr = Array.isArray(current) ? [...current] : [];
                                        if (!arr.find(r => r.id === ref.id)) arr.push(ref);
                                        newValue = arr;
                                    } else {
                                        newValue = ref;
                                    }

                                    StateEngine.dispatch('UPDATE_PROPS', {
                                        nodeId,
                                        props: { [fieldName]: newValue },
                                    });

                                    resultsEl.style.display = 'none';
                                    input.value = '';
                                    Inspector.update();
                                });
                            });
                        } catch (err) {
                            console.error('[Reference search error]', err);
                        }
                    }, 300);
                });
            });

            // Bind remove buttons on reference tags
            panel.querySelectorAll('.editor-ref-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const fieldName = btn.dataset.fieldName;
                    const removeId = parseInt(btn.dataset.refId);
                    const node = StateEngine.findNode(nodeId);
                    const current = node?.props?.[fieldName];

                    if (Array.isArray(current)) {
                        const newVal = current.filter(r => (typeof r === 'object' ? r.id : r) !== removeId);
                        StateEngine.dispatch('UPDATE_PROPS', { nodeId, props: { [fieldName]: newVal } });
                    } else {
                        StateEngine.dispatch('UPDATE_PROPS', { nodeId, props: { [fieldName]: null } });
                    }
                    Inspector.update();
                });
            });
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Context Menu
    // ════════════════════════════════════════════════════════════
    const ContextMenu = {
        el: null,

        init() {
            this.el = document.createElement('div');
            this.el.className = 'editor-context-menu';
            this.el.style.display = 'none';
            document.body.appendChild(this.el);

            document.addEventListener('click', () => this.hide());
            document.addEventListener('contextmenu', () => this.hide());
        },

        show(x, y, node) {
            const isLocked = node.meta?.locked;
            const isGlobal = node.meta?.global;
            const items = [
                { label: 'Bearbeiten', action: () => StateEngine.dispatch('SELECT_NODE', { nodeId: node.id }) },
                { label: 'Duplizieren', action: () => StateEngine.dispatch('DUPLICATE_NODE', { nodeId: node.id }), disabled: isLocked },
                { label: 'Als Komponente speichern', action: () => saveNodeAsComponent(node) },
                ...(isGlobal
                    ? [{ label: 'Verbindung lösen', action: () => disconnectGlobal(node) }]
                    : [{ label: 'Als Global speichern', action: () => saveNodeAsGlobal(node) }]
                ),
                { sep: true },
                { label: 'Nach oben', action: () => Canvas.moveNodeUp(node.id), disabled: isLocked },
                { label: 'Nach unten', action: () => Canvas.moveNodeDown(node.id), disabled: isLocked },
                { sep: true },
                { label: 'Löschen', action: () => StateEngine.dispatch('REMOVE_NODE', { nodeId: node.id }), danger: true, disabled: isLocked },
            ];

            this.el.innerHTML = items.map(item => {
                if (item.sep) return '<div class="editor-context-menu__sep"></div>';
                const cls = item.danger ? ' editor-context-menu__item--danger' : '';
                return `<button class="editor-context-menu__item${cls}" data-action="${item.label}">${item.label}</button>`;
            }).join('');

            // Bind actions
            this.el.querySelectorAll('.editor-context-menu__item').forEach((btn, idx) => {
                const realIdx = items.findIndex(i => i.label === btn.dataset.action);
                if (realIdx >= 0 && items[realIdx].action) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        items[realIdx].action();
                        this.hide();
                    });
                }
            });

            // Position
            this.el.style.display = 'block';
            this.el.style.left = Math.min(x, window.innerWidth - 200) + 'px';
            this.el.style.top = Math.min(y, window.innerHeight - 300) + 'px';
        },

        hide() {
            if (this.el) this.el.style.display = 'none';
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Breadcrumbs
    // ════════════════════════════════════════════════════════════
    const Breadcrumbs = {
        el: null,

        init() {
            this.el = document.getElementById('editor-breadcrumbs');
        },

        update() {
            if (!this.el) return;

            const path = StateEngine.state.selection.path;
            if (!path || path.length === 0) {
                this.el.innerHTML = `<span class="editor-breadcrumb" data-node-id="root_1">${CONFIG.contentType}</span>`;
                return;
            }

            this.el.innerHTML = path.map((nodeId, idx) => {
                const node = StateEngine.findNode(nodeId);
                const label = node?.label || node?.definition || node?.type || nodeId;
                const isLast = idx === path.length - 1;
                return `<span class="editor-breadcrumb${isLast ? '' : ''}" data-node-id="${nodeId}">${Canvas.escapeHtml(label)}</span>`;
            }).join('');

            // Bind breadcrumb clicks
            this.el.querySelectorAll('.editor-breadcrumb').forEach(bc => {
                bc.addEventListener('click', () => {
                    StateEngine.dispatch('SELECT_NODE', { nodeId: bc.dataset.nodeId });
                });
            });
        },
    };

    // ════════════════════════════════════════════════════════════
    //  UI Bindings
    // ════════════════════════════════════════════════════════════
    function bindUI() {
        // Sidebar toggles
        document.getElementById('toggle-catalog')?.addEventListener('click', () => {
            StateEngine.dispatch('TOGGLE_LEFT_SIDEBAR');
        });
        document.getElementById('toggle-inspector')?.addEventListener('click', () => {
            StateEngine.dispatch('TOGGLE_RIGHT_SIDEBAR');
        });
        // Floating toggles (visible when sidebars are collapsed)
        document.getElementById('toggle-catalog-float')?.addEventListener('click', () => {
            StateEngine.dispatch('TOGGLE_LEFT_SIDEBAR');
        });
        document.getElementById('toggle-inspector-float')?.addEventListener('click', () => {
            StateEngine.dispatch('TOGGLE_RIGHT_SIDEBAR');
        });

        // Device switcher
        document.querySelectorAll('.editor-device-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const device = btn.dataset.device;
                StateEngine.dispatch('SET_PREVIEW_MODE', { mode: device });

                document.querySelectorAll('.editor-device-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const canvas = document.getElementById('editor-canvas');
                if (canvas) canvas.dataset.device = device;
            });
        });

        // Undo / Redo
        document.getElementById('editor-undo')?.addEventListener('click', () => {
            StateEngine.dispatch('UNDO');
        });
        document.getElementById('editor-redo')?.addEventListener('click', () => {
            StateEngine.dispatch('REDO');
        });

        // Save
        document.getElementById('editor-save')?.addEventListener('click', () => saveContent());

        // Preview
        document.getElementById('editor-preview')?.addEventListener('click', () => openPreview());

        // Versions
        document.getElementById('editor-versions')?.addEventListener('click', () => openVersions());

        // Canvas click deselect
        document.getElementById('editor-canvas-inner')?.addEventListener('click', (e) => {
            if (e.target.id === 'editor-canvas-inner' || e.target.closest('.editor-canvas__empty')) {
                StateEngine.dispatch('DESELECT');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+S → Save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveContent();
            }
            // Ctrl+Z → Undo
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                StateEngine.dispatch('UNDO');
            }
            // Ctrl+Shift+Z or Ctrl+Y → Redo
            if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                e.preventDefault();
                StateEngine.dispatch('REDO');
            }
            // Escape → Deselect
            if (e.key === 'Escape') {
                StateEngine.dispatch('DESELECT');
                ContextMenu.hide();
            }
            // Delete → Remove selected node
            if (e.key === 'Delete' && StateEngine.state.selection.activeNodeId) {
                // Don't delete if focus is in an input
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
                StateEngine.dispatch('REMOVE_NODE', { nodeId: StateEngine.state.selection.activeNodeId });
            }
        });

        // Modals
        document.getElementById('preview-close')?.addEventListener('click', () => {
            document.getElementById('editor-preview-modal').style.display = 'none';
        });
        document.getElementById('versions-close')?.addEventListener('click', () => {
            document.getElementById('editor-versions-modal').style.display = 'none';
        });
        document.querySelectorAll('.editor-modal__backdrop').forEach(bd => {
            bd.addEventListener('click', () => {
                bd.closest('.editor-modal').style.display = 'none';
            });
        });
    }

    // ════════════════════════════════════════════════════════════
    //  Status Workflow
    // ════════════════════════════════════════════════════════════
    const STATUS_LABELS = {
        draft: 'Entwurf',
        review: 'In Prüfung',
        published: 'Veröffentlicht',
        archived: 'Archiviert',
    };

    const StatusWorkflow = {
        currentStatus: CONFIG.contentStatus || 'draft',
        availableTransitions: [],

        init() {
            const badge = document.getElementById('editor-status-badge');
            if (!badge) return;

            badge.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleMenu();
            });

            document.addEventListener('click', () => this.hideMenu());
            this.updateBadge();
        },

        setTransitions(transitions) {
            this.availableTransitions = transitions || [];
        },

        setStatus(status) {
            this.currentStatus = status;
            this.updateBadge();
        },

        updateBadge() {
            const badge = document.getElementById('editor-status-badge');
            if (!badge) return;
            badge.dataset.status = this.currentStatus;
            const label = STATUS_LABELS[this.currentStatus] || this.currentStatus;
            const hasTransitions = this.availableTransitions.length > 0;
            badge.innerHTML = label + (hasTransitions
                ? ' <svg class="editor-status-badge__arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>'
                : '');
        },

        toggleMenu() {
            const menu = document.getElementById('editor-status-menu');
            if (!menu) return;

            if (menu.style.display !== 'none') {
                this.hideMenu();
                return;
            }

            if (this.availableTransitions.length === 0) return;

            menu.innerHTML = '';
            this.availableTransitions.forEach(target => {
                const item = document.createElement('button');
                item.className = 'editor-status-dropdown__item';
                item.dataset.status = target;
                item.textContent = STATUS_LABELS[target] || target;
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.changeStatus(target);
                });
                menu.appendChild(item);
            });

            menu.style.display = '';
        },

        hideMenu() {
            const menu = document.getElementById('editor-status-menu');
            if (menu) menu.style.display = 'none';
        },

        async changeStatus(newStatus) {
            this.hideMenu();
            const label = STATUS_LABELS[newStatus] || newStatus;
            if (!confirm(`Status ändern auf "${label}"?`)) return;

            try {
                const result = await API.changeState(newStatus);
                const data = result.data || result;
                this.currentStatus = data.newState || newStatus;
                this.availableTransitions = data.availableTransitions || [];
                this.updateBadge();
                showToast(`Status geändert: ${STATUS_LABELS[this.currentStatus] || this.currentStatus}`, 'success');
            } catch (err) {
                showToast('Status-Änderung fehlgeschlagen: ' + (err.message || 'Fehler'), 'error');
            }
        },
    };

    // ════════════════════════════════════════════════════════════
    //  Actions
    // ════════════════════════════════════════════════════════════
    async function saveContent() {
        try {
            const editorData = StateEngine.getSerializableDocument();
            await API.save(editorData);
            StateEngine.dispatch('MARK_SAVED');
            showToast('Inhalt gespeichert', 'success');
        } catch (err) {
            console.error('Save error:', err);
            showToast('Fehler beim Speichern: ' + (err.message || 'Unbekannter Fehler'), 'error');
        }
    }

    async function openPreview() {
        try {
            const editorData = StateEngine.getSerializableDocument();
            const result = await API.preview(editorData);
            const modal = document.getElementById('editor-preview-modal');
            const frame = document.getElementById('editor-preview-frame');

            if (modal && frame && result.data?.html) {
                frame.srcdoc = result.data.html;
                modal.style.display = '';
            }
        } catch (err) {
            showToast('Vorschau konnte nicht geladen werden', 'error');
        }
    }

    async function openVersions() {
        const list = document.getElementById('versions-list');
        const modal = document.getElementById('editor-versions-modal');
        if (!modal || !list) return;

        modal.style.display = '';
        list.innerHTML = '<div class="editor-loading"><div class="editor-spinner"></div></div>';

        try {
            const result = await API.load();
            const versions = result.data?.versions || [];

            if (versions.length === 0) {
                list.innerHTML = '<p style="color:var(--text-muted);font-size:12px;">Keine Versionen vorhanden</p>';
                return;
            }

            list.innerHTML = versions.map((v, idx) => `
                <div class="editor-version-item">
                    <div class="editor-version-item__info">
                        <span class="editor-version-item__number">Version ${Canvas.escapeHtml(String(v.version || '?'))}</span>
                        <span class="editor-version-item__date">${Canvas.escapeHtml(v.createdAt || '')}</span>
                        ${v.note ? `<span class="editor-version-item__note">${Canvas.escapeHtml(v.note)}</span>` : ''}
                    </div>
                    ${idx > 0 ? `<button class="editor-version-item__restore" data-version="${Canvas.escapeHtml(String(v.version || ''))}" title="Diese Version wiederherstellen">↩</button>` : '<span class="editor-version-item__current">Aktuell</span>'}
                </div>
            `).join('');

            // Bind restore actions
            list.querySelectorAll('.editor-version-item__restore').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const ver = btn.dataset.version;
                    if (!confirm(`Version ${ver} wiederherstellen? Ungespeicherte Änderungen gehen verloren.`)) return;
                    try {
                        const restoreResult = await API.request('POST', `${CONFIG.apiBase}/${CONFIG.contentId}/restore`, { version: parseInt(ver, 10) });
                        if (restoreResult.data?.editor?.root) {
                            StateEngine.dispatch('SET_DOCUMENT', { root: restoreResult.data.editor.root });
                            StateEngine.state.meta.dirty = false;
                            showToast(`Version ${ver} wiederhergestellt`, 'success');
                            modal.style.display = 'none';
                        }
                    } catch (err) {
                        showToast('Version konnte nicht wiederhergestellt werden', 'error');
                    }
                });
            });
        } catch (err) {
            list.innerHTML = '<p style="color:var(--danger);font-size:12px;">Fehler beim Laden der Versionen</p>';
        }
    }

    async function saveNodeAsComponent(node) {
        const name = prompt('Name der neuen Komponente:');
        if (!name) return;

        try {
            const result = await API.saveComponent(name, node);
            const data = result.data || result;

            if (data.definition) {
                definitions.components = definitions.components || {};
                definitions.components[data.id] = data.definition;
                Catalog.init();
            }

            showToast(`Komponente "${name}" gespeichert`, 'success');
        } catch (err) {
            showToast('Speichern fehlgeschlagen: ' + (err.message || 'Fehler'), 'error');
        }
    }

    async function saveNodeAsGlobal(node) {
        const name = prompt('Name der globalen Komponente:');
        if (!name) return;

        try {
            const result = await API.saveGlobal(name, node);
            const data = result.data || result;
            const refId = data.referenceId;

            // Mark the node as global
            StateEngine.dispatch('UPDATE_PROPS', {
                nodeId: node.id,
                props: { ...node.props },
                meta: { ...(node.meta || {}), global: true, referenceId: refId },
            });

            showToast(`"${name}" als globale Komponente gespeichert`, 'success');
        } catch (err) {
            showToast('Speichern fehlgeschlagen: ' + (err.message || 'Fehler'), 'error');
        }
    }

    function disconnectGlobal(node) {
        if (!confirm('Verbindung zur globalen Komponente lösen? Änderungen werden nicht mehr synchronisiert.')) return;

        const newMeta = { ...(node.meta || {}) };
        delete newMeta.global;
        delete newMeta.referenceId;

        StateEngine.dispatch('UPDATE_PROPS', {
            nodeId: node.id,
            props: { ...node.props },
            meta: newMeta,
        });

        showToast('Verbindung gelöst – Element ist jetzt lokal', 'success');
    }

    // ════════════════════════════════════════════════════════════
    //  State Change Handler (re-render loop)
    // ════════════════════════════════════════════════════════════
    StateEngine.subscribe((state, action) => {
        // Re-render canvas for document/selection changes
        const renderActions = ['SET_DOCUMENT', 'ADD_NODE', 'REMOVE_NODE', 'MOVE_NODE', 'UPDATE_PROPS', 'DUPLICATE_NODE', 'UNDO', 'REDO', 'SELECT_NODE', 'DESELECT'];
        if (renderActions.includes(action)) {
            Canvas.render();
            // Defensive DOM reconciliation: ensure only active node is visually marked
            // Some race conditions or legacy DOM can lead to multiple elements showing
            // as selected. Force a single selected element based on state.
            try {
                document.querySelectorAll('.editor-node.selected').forEach(el => el.classList.remove('selected'));
                const activeId = StateEngine.state.selection.activeNodeId;
                if (activeId) {
                    const selEl = document.querySelector(`.editor-node[data-node-id="${activeId}"]`);
                    if (selEl) selEl.classList.add('selected');
                }
            } catch (err) {
                // ignore any DOM errors
                console.error('[Editor] selection reconciliation error', err);
            }
        }

        // Update inspector for selection changes
        if (['SELECT_NODE', 'DESELECT', 'UPDATE_PROPS', 'ADD_NODE', 'REMOVE_NODE', 'UNDO', 'REDO'].includes(action)) {
            Inspector.update();
            Breadcrumbs.update();
        }

        // Sidebar visibility
        if (action === 'TOGGLE_LEFT_SIDEBAR') {
            document.getElementById('editor-catalog-sidebar')?.classList.toggle('collapsed', !state.ui.leftSidebarOpen);
            // keep a body-level flag so floating toggles/CSS can react
            editorEl.classList.toggle('left-collapsed', !state.ui.leftSidebarOpen);
        }
        if (action === 'TOGGLE_RIGHT_SIDEBAR') {
            document.getElementById('editor-inspector-sidebar')?.classList.toggle('collapsed', !state.ui.rightSidebarOpen);
            // keep a body-level flag so floating toggles/CSS can react
            editorEl.classList.toggle('right-collapsed', !state.ui.rightSidebarOpen);
        }

        // Dirty indicator
        const dirtyEl = document.getElementById('editor-dirty');
        if (dirtyEl) dirtyEl.style.display = state.meta.dirty ? '' : 'none';

        // Undo/Redo button states
        const undoBtn = document.getElementById('editor-undo');
        const redoBtn = document.getElementById('editor-redo');
        if (undoBtn) undoBtn.disabled = state.history.past.length === 0;
        if (redoBtn) redoBtn.disabled = state.history.future.length === 0;

        // Drag state class
        editorEl.classList.toggle('dragging', state.ui.isDragging);
    });

    // ════════════════════════════════════════════════════════════
    //  Initialization
    // ════════════════════════════════════════════════════════════
    async function init() {
        Canvas.init();
        Inspector.init();
        ContextMenu.init();
        Breadcrumbs.init();
        StatusWorkflow.init();
        bindUI();

        // Load data from API
        try {
            const result = await API.load();
            const data = result.data || result;

            // Store definitions
            if (data.definitions) {
                definitions = data.definitions;
            }

            // Initialize catalog with definitions
            Catalog.init();

            // Set status workflow transitions
            if (data.content?.status) {
                StatusWorkflow.setStatus(data.content.status);
            }
            if (data.availableTransitions) {
                StatusWorkflow.setTransitions(data.availableTransitions);
                StatusWorkflow.updateBadge();
            }

            // Set document from loaded editor data
            if (data.editor?.root) {
                // Defensive: ensure every node has an `id` so selection and DOM bindings work
                try { ensureNodeIds(data.editor.root); } catch (err) { /* ignore */ }
                StateEngine.dispatch('SET_DOCUMENT', {
                    root: data.editor.root,
                });
            }

            // Initial render
            Canvas.render();
            Inspector.update();
            Breadcrumbs.update();

            console.log('[Chamy Editor] Initialized', {
                contentId: CONFIG.contentId,
                contentType: CONFIG.contentType,
                definitions: Object.keys(definitions).reduce((acc, k) => { acc[k] = Object.keys(definitions[k]).length; return acc; }, {}),
            });
        } catch (err) {
            console.error('[Chamy Editor] Init error:', err);
            showToast('Editor konnte nicht geladen werden: ' + (err.message || 'API-Fehler'), 'error');

            // Fallback: load definitions separately and start with empty canvas
            try {
                const defResult = await API.loadDefinitions();
                definitions = defResult.data || defResult;
                Catalog.init();
                Canvas.render();
            } catch (defErr) {
                console.error('[Chamy Editor] Definitions load failed:', defErr);
            }
        }
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
