define(['jquery'], function($) {
    const state = {
        pages: [],
        elements: [],
        currentPage: 'p1',
        zoom: 1,
        editId: null,
        selectedId: null,
        signatureEditId: null,
        config: {}
    };

    const pageSize = {w: 1123, h: 794};

    function uid(prefix) {
        return prefix + '-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
    }

    function showModal(selector) {
        const el = document.querySelector(selector);
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(el).show();
        } else {
            $(selector).modal('show');
        }
    }

    function hideModal(selector) {
        const el = document.querySelector(selector);
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(el).hide();
        } else {
            $(selector).modal('hide');
        }
    }


    function setTextEditorValue(value) {
        const field = document.getElementById('ec-text');
        const content = value || '';
        if (window.tinyMCE && window.tinyMCE.get && window.tinyMCE.get('ec-text')) {
            window.tinyMCE.get('ec-text').setContent(content);
        } else if (field) {
            field.value = content;
        }
    }

    function appendTextEditorValue(value) {
        const current = getTextEditorValue();
        const separator = current ? ' ' : '';
        setTextEditorValue(current + separator + value);
    }

    function getTextEditorValue() {
        const field = document.getElementById('ec-text');
        if (window.tinyMCE && window.tinyMCE.get && window.tinyMCE.get('ec-text')) {
            return window.tinyMCE.get('ec-text').getContent().trim();
        }
        return field ? (field.value || '').trim() : '';
    }

    function syncHidden() {
        $('#ec-pagesjson').val(JSON.stringify(state.pages));
        $('#ec-elementsjson').val(JSON.stringify(state.elements));
    }

    function resolveText(text) {
        return String(text || '').replace(/\{([a-zA-Z0-9_\-]+)\}/g, function(match, key) {
            return Object.prototype.hasOwnProperty.call(state.config.previewdata, key) ? state.config.previewdata[key] : '';
        });
    }

    function renderTabs() {
        const tabs = $('#ec-pages-tabs').empty();
        state.pages.forEach(function(page, index) {
            const item = $('<span class="ec-page-tab-item mb-1"></span>').appendTo(tabs);
            $('<button type="button" class="btn btn-sm ec-page-tab"></button>')
                .addClass(page.id === state.currentPage ? 'btn-primary' : 'btn-outline-secondary')
                .text(page.name || ('Página ' + (index + 1)))
                .on('click', function() {
                    state.currentPage = page.id;
                    render();
                })
                .appendTo(item);
            $('<button type="button" class="btn btn-sm btn-outline-danger ec-page-remove" title="Remover página" aria-label="Remover página"><i class="fa fa-trash" aria-hidden="true"></i></button>')
                .prop('disabled', state.pages.length <= 1)
                .on('click', function(ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    removePage(page.id);
                })
                .appendTo(item);
        });
    }



    function getSignatureName(element) {
        const current = String(element.name || '').trim();
        if (current && !/^Assinatura\s+\d+$/i.test(current)) {
            return current;
        }
        const signatures = state.elements.filter(function(item) {
            return item.type === 'signature' && item.pageid === element.pageid;
        });
        const index = signatures.findIndex(function(item) {
            return item.id === element.id;
        });
        return 'Assinatura ' + (index >= 0 ? index + 1 : signatures.length + 1);
    }

    function normalizeSignatureNames() {
        const counters = {};
        state.elements.forEach(function(element) {
            if (element.type !== 'signature') {
                return;
            }
            const pageid = element.pageid || state.currentPage;
            counters[pageid] = (counters[pageid] || 0) + 1;
            const current = String(element.name || '').trim();
            if (!current || /^Assinatura\s+\d+$/i.test(current)) {
                element.name = 'Assinatura ' + counters[pageid];
            }
        });
    }

    function elementTitle(element) {
        if (!element) {
            return '';
        }
        if (element.name) {
            return element.name;
        }
        if (element.type === 'signature') {
            return getSignatureName(element);
        }
        if (element.type === 'image') {
            return 'Imagem';
        }
        if (element.type === 'border') {
            return 'Borda';
        }
        const clean = $('<div>').html(element.text || '').text().trim();
        return clean || 'Texto';
    }

    function elementIcon(element) {
        if (!element) {
            return 'fa-square-o';
        }
        if (element.type === 'signature') {
            return 'fas fa-signature';
        }
        if (element.type === 'image') {
            return 'fa-file-image-o';
        }
        if (element.type === 'border') {
            return 'fa-square-o';
        }
        return 'fa-font';
    }

    function render() {
        const stage = $('#ec-stage');
        const page = state.pages.find(p => p.id === state.currentPage) || state.pages[0];
        if (!page) {
            return;
        }
        normalizeSignatureNames();
        stage.empty().css({
            width: pageSize.w + 'px',
            height: pageSize.h + 'px',
            transform: 'scale(' + state.zoom + ')',
            backgroundImage: page.background ? 'url(' + page.background + ')' : 'none'
        });
        state.elements.filter(e => e.pageid === page.id).forEach(function(element) {
            const item = $('<div class="ec-element" tabindex="0"></div>').attr('data-id', element.id).toggleClass('ec-selected', state.selectedId === element.id).toggleClass('ec-element-no-duplicate', element.type === 'signature').css({
                left: element.x + 'px',
                top: element.y + 'px',
                width: element.w + 'px',
                height: element.h + 'px'
            });
            if (element.type === 'image' || element.type === 'signature') {
                item.addClass(element.type === 'signature' ? 'ec-element-signature' : 'ec-element-image');
                if (element.src || element.mask) {
                    const img = $('<img alt="">').attr('src', element.src || element.mask);
                    img.on('load', function() {
                        const naturalw = this.naturalWidth || this.width || 0;
                        const naturalh = this.naturalHeight || this.height || 0;
                        if (naturalw && naturalh && (!element.naturalw || !element.naturalh)) {
                            element.naturalw = naturalw;
                            element.naturalh = naturalh;
                            element.h = Math.max(20, Math.round((Number(element.w || 260) * naturalh) / naturalw));
                            item.css('height', element.h + 'px');
                            syncHidden();
                        }
                    });
                    item.append(img);
                }
            } else if (element.type === 'border') {
                item.addClass('ec-element-border');
            } else {
                item.addClass('ec-element-text').css({
                    fontSize: (element.size || 24) + 'px',
                    color: element.color || '#111111',
                    fontFamily: element.font || 'helvetica',
                    fontWeight: element.bold ? '700' : '400',
                    fontStyle: element.italic ? 'italic' : 'normal',
                    textAlign: mapAlign(element.align || 'L')
                }).html(resolveText(element.text));
            }
            $('<button type="button" class="ec-element-edit" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>').on('click', function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                state.selectedId = element.id;
                openEdit(element.id);
            }).appendTo(item);
            if (element.type !== 'signature') {
                $('<button type="button" class="ec-element-duplicate" title="Duplicar"><i class="fa fa-files-o" aria-hidden="true"></i></button>').on('click', function(ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    duplicateElement(element.id);
                }).appendTo(item);
            }
            $('<button type="button" class="ec-element-delete" title="Excluir"><i class="fa fa-trash" aria-hidden="true"></i></button>').on('click', function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                removeElement(element.id);
            }).appendTo(item);
            $('<span class="ec-resize-handle" title="Redimensionar"></span>').appendTo(item);
            makeInteractive(item[0]);
            item.on('click', function(ev) {
                ev.stopPropagation();
                state.selectedId = element.id;
                render();
            });
            stage.append(item);
        });
        $('#ec-zoom-label').text(Math.round(state.zoom * 100) + '%');
        renderTabs();
        renderItemsPanel();
        syncHidden();
    }

    function mapAlign(align) {
        if (align === 'C') return 'center';
        if (align === 'R') return 'right';
        return 'left';
    }



    function renderItemsPanel() {
        const panel = $('#ec-items-list').empty();
        const page = state.pages.find(p => p.id === state.currentPage) || state.pages[0];
        if (!panel.length || !page) {
            return;
        }
        if (page.background) {
            const bgrow = $('<div class="ec-item-row ec-item-background"></div>').appendTo(panel);
            $('<span class="ec-item-move"><i class="fa fa-file-image-o" aria-hidden="true"></i></span>').appendTo(bgrow);
            $('<span class="ec-item-title"></span>').text('Imagem de fundo').appendTo(bgrow);
            $('<button type="button" class="ec-item-action" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>').on('click', function() {
                openBackgroundEdit();
            }).appendTo(bgrow);
            $('<button type="button" class="ec-item-action" title="Remover"><i class="fa fa-trash" aria-hidden="true"></i></button>').on('click', function() {
                page.background = '';
                syncHidden();
                render();
            }).appendTo(bgrow);
        }
        const items = state.elements.filter(e => e.pageid === page.id);
        if (!page.background && !items.length) {
            $('<div class="ec-items-empty">Nenhum item adicionado.</div>').appendTo(panel);
            return;
        }
        items.forEach(function(element) {
            const row = $('<div class="ec-item-row" draggable="true"></div>').attr('data-id', element.id).toggleClass('active', state.selectedId === element.id).toggleClass('ec-item-no-duplicate', element.type === 'signature').appendTo(panel);
            $('<span class="ec-item-move"><i class="fa fa-arrows" aria-hidden="true"></i></span>').appendTo(row);
            $('<span class="ec-item-type"><i class="fa" aria-hidden="true"></i></span>').find('i').addClass(elementIcon(element)).end().appendTo(row);
            $('<span class="ec-item-title"></span>').text(elementTitle(element)).attr('title', elementTitle(element)).on('dblclick', function(ev) {
                ev.preventDefault();
                renameElement(element.id);
            }).appendTo(row);
            $('<button type="button" class="ec-item-action" title="Renomear"><i class="fa fa-pencil" aria-hidden="true"></i></button>').on('click', function(ev) {
                ev.preventDefault();
                renameElement(element.id);
            }).appendTo(row);
            if (element.type !== 'signature') {
                $('<button type="button" class="ec-item-action" title="Duplicar"><i class="fa fa-files-o" aria-hidden="true"></i></button>').on('click', function(ev) {
                    ev.preventDefault();
                    duplicateElement(element.id);
                }).appendTo(row);
            }
            $('<button type="button" class="ec-item-action" title="Editar"><i class="fa fa-cog" aria-hidden="true"></i></button>').on('click', function(ev) {
                ev.preventDefault();
                openEdit(element.id);
            }).appendTo(row);
            $('<button type="button" class="ec-item-action" title="Remover"><i class="fa fa-trash" aria-hidden="true"></i></button>').on('click', function(ev) {
                ev.preventDefault();
                removeElement(element.id);
            }).appendTo(row);
            row.on('dragstart', function(ev) {
                ev.originalEvent.dataTransfer.setData('text/plain', element.id);
                ev.originalEvent.dataTransfer.effectAllowed = 'move';
                row.addClass('dragging');
            });
            row.on('dragend', function() {
                row.removeClass('dragging');
            });
            row.on('dragover', function(ev) {
                ev.preventDefault();
                ev.originalEvent.dataTransfer.dropEffect = 'move';
            });
            row.on('drop', function(ev) {
                ev.preventDefault();
                const sourceid = ev.originalEvent.dataTransfer.getData('text/plain');
                reorderElement(sourceid, element.id);
            });
            row.on('click', function(ev) {
                if ($(ev.target).closest('button').length) {
                    return;
                }
                state.selectedId = element.id;
                render();
            });
        });
    }

    function reorderElement(sourceid, targetid) {
        if (!sourceid || !targetid || sourceid === targetid) {
            return;
        }
        const sourceindex = state.elements.findIndex(e => e.id === sourceid);
        const targetindex = state.elements.findIndex(e => e.id === targetid);
        if (sourceindex < 0 || targetindex < 0) {
            return;
        }
        const source = state.elements[sourceindex];
        const target = state.elements[targetindex];
        if (source.pageid !== target.pageid) {
            return;
        }
        state.elements.splice(sourceindex, 1);
        const newtargetindex = state.elements.findIndex(e => e.id === targetid);
        state.elements.splice(newtargetindex, 0, source);
        state.selectedId = sourceid;
        syncHidden();
        render();
    }

    function removeElement(id) {
        state.elements = state.elements.filter(e => e.id !== id);
        if (state.selectedId === id) {
            state.selectedId = null;
        }
        syncHidden();
        render();
    }

    function renameElement(id) {
        const element = state.elements.find(e => e.id === id);
        if (!element) {
            return;
        }
        $('#ec-rename-id').val(id);
        $('#ec-rename-name').val(elementTitle(element));
        showModal('#ec-rename-modal');
        setTimeout(function() {
            $('#ec-rename-name').trigger('focus').select();
        }, 150);
    }

    function saveRename() {
        const id = $('#ec-rename-id').val();
        const element = state.elements.find(e => e.id === id);
        if (!element) {
            hideModal('#ec-rename-modal');
            return;
        }
        const name = String($('#ec-rename-name').val() || '').trim();
        element.name = name || elementTitle(element);
        hideModal('#ec-rename-modal');
        syncHidden();
        render();
    }

    function openBackgroundEdit() {
        state.editId = null;
        $('#ec-image-file').val('');
        $('#ec-image-bg').prop('checked', true).prop('disabled', true);
        $('#ec-image-save').text('Salvar');
        showModal('#ec-image-modal');
    }

    function duplicateElement(id) {
        const element = state.elements.find(e => e.id === id);
        if (!element) {
            return;
        }
        const copy = JSON.parse(JSON.stringify(element));
        copy.id = uid(element.type === 'image' ? 'img' : (element.type === 'signature' ? 'sig' : 'el'));
        copy.x = Math.min(pageSize.w - Number(copy.w || 120), Number(copy.x || 0) + 24);
        copy.y = Math.min(pageSize.h - Number(copy.h || 40), Number(copy.y || 0) + 24);
        copy.x = Math.max(0, Math.round(copy.x));
        copy.y = Math.max(0, Math.round(copy.y));
        state.elements.push(copy);
        state.selectedId = copy.id;
        syncHidden();
        render();
    }

    function makeInteractive(node) {
        let mode = null, startX = 0, startY = 0, baseX = 0, baseY = 0, baseW = 0, baseH = 0, baseRatio = 1, id = null;
        node.addEventListener('pointerdown', function(ev) {
            if (ev.target.closest('button')) return;
            id = node.getAttribute('data-id');
            const element = state.elements.find(e => e.id === id);
            if (!element) return;
            state.selectedId = id;
            $('.ec-element').removeClass('ec-selected');
            node.classList.add('ec-selected');
            mode = ev.target.classList.contains('ec-resize-handle') ? 'resize' : 'drag';
            startX = ev.clientX;
            startY = ev.clientY;
            baseX = Number(element.x || 0);
            baseY = Number(element.y || 0);
            baseW = Number(element.w || 120);
            baseH = Number(element.h || 40);
            baseRatio = baseW > 0 && baseH > 0 ? baseW / baseH : 1;
            node.setPointerCapture(ev.pointerId);
            node.classList.add(mode === 'resize' ? 'resizing' : 'dragging');
            ev.preventDefault();
        });
        node.addEventListener('pointermove', function(ev) {
            if (!id) return;
            const element = state.elements.find(e => e.id === id);
            if (!element) return;
            if (mode === 'resize') {
                let w = baseW + (ev.clientX - startX) / state.zoom;
                let h = baseH + (ev.clientY - startY) / state.zoom;
                if (element.type === 'image' || element.type === 'signature' || ev.shiftKey) {
                    if (Math.abs(w - baseW) >= Math.abs(h - baseH)) {
                        w = Math.max(20, w);
                        h = w / baseRatio;
                    } else {
                        h = Math.max(20, h);
                        w = h * baseRatio;
                    }
                }
                element.w = Math.max(20, Math.round(w));
                element.h = Math.max(20, Math.round(h));
                node.style.width = element.w + 'px';
                node.style.height = element.h + 'px';
                syncHidden();
                return;
            }
            let x = baseX + (ev.clientX - startX) / state.zoom;
            let y = baseY + (ev.clientY - startY) / state.zoom;
            const snap = getSnap(element, x, y);
            x = snap.x;
            y = snap.y;
            element.x = Math.max(0, Math.round(x));
            element.y = Math.max(0, Math.round(y));
            node.style.left = element.x + 'px';
            node.style.top = element.y + 'px';
            showGuides(snap);
            syncHidden();
        });
        node.addEventListener('pointerup', function(ev) {
            if (!id) return;
            node.releasePointerCapture(ev.pointerId);
            node.classList.remove('dragging', 'resizing');
            id = null;
            mode = null;
            hideGuides();
            render();
        });
        node.addEventListener('pointercancel', function() {
            id = null;
            mode = null;
            node.classList.remove('dragging', 'resizing');
            hideGuides();
        });
    }

    function getSnap(active, x, y) {
        const threshold = 6;
        const result = {x, y, guideX: null, guideY: null};
        if (active) {
            const pageCenterX = pageSize.w / 2;
            const pageCenterY = pageSize.h / 2;
            const activeCenterX = x + active.w / 2;
            const activeCenterY = y + active.h / 2;
            if (Math.abs(activeCenterX - pageCenterX) <= threshold) {
                result.x = x + (pageCenterX - activeCenterX);
                x = result.x;
                result.guideX = pageCenterX;
            }
            if (Math.abs(activeCenterY - pageCenterY) <= threshold) {
                result.y = y + (pageCenterY - activeCenterY);
                y = result.y;
                result.guideY = pageCenterY;
            }
        }
        state.elements.forEach(function(other) {
            if (!active || other.id === active.id || other.pageid !== active.pageid) return;
            const otherCenterX = other.x + other.w / 2;
            const otherCenterY = other.y + other.h / 2;
            const activeCenterX = x + active.w / 2;
            const activeCenterY = y + active.h / 2;
            if (Math.abs(activeCenterX - otherCenterX) <= threshold) {
                result.x = x + (otherCenterX - activeCenterX);
                x = result.x;
                result.guideX = otherCenterX;
            }
            if (Math.abs(activeCenterY - otherCenterY) <= threshold) {
                result.y = y + (otherCenterY - activeCenterY);
                y = result.y;
                result.guideY = otherCenterY;
            }
        });
        return result;
    }

    function showGuides(snap) {
        const gx = $('#ec-guide-x');
        const gy = $('#ec-guide-y');
        if (snap.guideY !== null) {
            gx.css({top: (snap.guideY * state.zoom) + 'px'}).show();
        } else {
            gx.hide();
        }
        if (snap.guideX !== null) {
            gy.css({left: (snap.guideX * state.zoom) + 'px'}).show();
        } else {
            gy.hide();
        }
    }

    function hideGuides() {
        $('#ec-guide-x,#ec-guide-y').hide();
    }

    function addSelectOptions(select, fields, label) {
        if (!fields || !Object.keys(fields).length) {
            return;
        }
        if (label) {
            $('<option disabled></option>').text(label).appendTo(select);
        }
        Object.keys(fields).forEach(function(key) {
            $('<option></option>').val(key).text(fields[key] + ' {' + key + '}').appendTo(select);
        });
    }

    function fillSelects() {
        const userselect = $('#ec-userfield-select').empty();
        $('<option></option>').val('').text('Selecionar campo').appendTo(userselect);
        addSelectOptions(userselect, state.config.userfields || {}, 'Campos de usuário');
        if (Object.keys(state.config.customfields || {}).length) {
            addSelectOptions(userselect, state.config.customfields || {}, 'Campos customizados');
        }

        const dateselect = $('#ec-datefield-select').empty();
        addSelectOptions(dateselect, state.config.datefields || {}, 'Datas');
        dateselect.val('currentdate');

        const concatselect = $('#ec-concatfield-select').empty();
        $('<option></option>').val('').text('Selecionar campo').appendTo(concatselect);
        addSelectOptions(concatselect, state.config.userfields || {}, 'Campos de usuário');
        if (Object.keys(state.config.customfields || {}).length) {
            addSelectOptions(concatselect, state.config.customfields || {}, 'Campos customizados');
        }
        addSelectOptions(concatselect, state.config.coursefields || {}, 'Campos do curso');
        addSelectOptions(concatselect, state.config.datefields || {}, 'Datas');
    }

    function openField(type) {
        state.editId = null;
        $('#ec-modal-type').val(type);
        setTextEditorValue(type === 'date' ? '{currentdate}' : '');
        $('#ec-size').val(24);
        $('#ec-color').val('#111111');
        $('#ec-customfield').val('');
        $('#ec-concatfield-select').val('');
        $('.ec-userfield-row').toggle(type === 'userfield');
        $('.ec-customfield-row').toggle(type === 'userfield');
        $('.ec-datefield-row').toggle(type === 'date');
        $('.ec-concatfield-row').toggle(type === 'concat');
        $('#ec-field-save').text('Adicionar');
        showModal('#ec-field-modal');
    }

    function openEdit(id) {
        const element = state.elements.find(e => e.id === id);
        if (!element || element.type === 'border') return;
        if (element.type === 'signature') return openSignature(element.id);
        if (element.type === 'image') return openImage(element.id);
        state.editId = id;
        $('#ec-modal-type').val(element.type);
        setTextEditorValue(element.text || '');
        $('#ec-size').val(element.size || 24);
        $('#ec-color').val(element.color || '#111111');
        $('.ec-userfield-row,.ec-customfield-row,.ec-datefield-row,.ec-concatfield-row').hide();
        $('#ec-field-save').text('Salvar');
        showModal('#ec-field-modal');
    }

    function saveField() {
        const type = $('#ec-modal-type').val() || 'text';
        let text = getTextEditorValue();
        if (type === 'userfield' && !text) {
            const custom = ($('#ec-customfield').val() || '').trim().replace(/[{}]/g, '');
            const field = custom || $('#ec-userfield-select').val();
            if (field) {
                text = '{' + field + '}';
            }
        }
        if (type === 'date' && !text) {
            text = '{' + ($('#ec-datefield-select').val() || 'currentdate') + '}';
        }
        const data = {
            type: type,
            text: text,
            size: Number($('#ec-size').val() || 24),
            color: normalizeHex($('#ec-color').val() || '#111111'),
            font: 'helvetica',
            align: 'L',
            bold: false,
            italic: false
        };
        if (state.editId) {
            Object.assign(state.elements.find(e => e.id === state.editId), data);
        } else {
            state.elements.push(Object.assign({id: uid('el'), pageid: state.currentPage, x: 80, y: 80, w: 420, h: 60}, data));
        }
        hideModal('#ec-field-modal');
        render();
    }

    function openImage(id) {
        state.editId = id || null;
        $('#ec-image-file').val('');
        $('#ec-image-bg').prop('disabled', false).prop('checked', false);
        $('#ec-image-save').text(state.editId ? 'Salvar' : 'Adicionar');
        showModal('#ec-image-modal');
    }

    function saveImage() {
        const file = document.getElementById('ec-image-file').files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            const src = ev.target.result;
            const page = state.pages.find(p => p.id === state.currentPage);
            const finish = function(naturalw, naturalh) {
                if ($('#ec-image-bg').is(':checked')) {
                    page.background = src;
                } else if (state.editId) {
                    const element = state.elements.find(e => e.id === state.editId);
                    if (element) {
                        element.src = src;
                        element.naturalw = naturalw || 0;
                        element.naturalh = naturalh || 0;
                        if (naturalw && naturalh) {
                            element.h = Math.max(20, Math.round((Number(element.w || 260) * naturalh) / naturalw));
                        }
                    }
                } else {
                    const width = 260;
                    const height = naturalw && naturalh ? Math.max(20, Math.round((width * naturalh) / naturalw)) : 140;
                    state.elements.push({id: uid('img'), pageid: state.currentPage, type: 'image', src: src, x: 80, y: 80, w: width, h: height, naturalw: naturalw || 0, naturalh: naturalh || 0});
                }
                state.editId = null;
                $('#ec-image-bg').prop('disabled', false);
                $('#ec-image-save').text('Adicionar');
                hideModal('#ec-image-modal');
                render();
            };
            const img = new Image();
            img.onload = function() { finish(img.naturalWidth || img.width, img.naturalHeight || img.height); };
            img.onerror = function() { finish(0, 0); };
            img.src = src;
        };
        reader.readAsDataURL(file);
    }


    function readFileAsDataURL(file, done) {
        if (!file) {
            done('');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(ev) { done(ev.target.result || ''); };
        reader.readAsDataURL(file);
    }

    function openSignature(id) {
        state.signatureEditId = id || null;
        const element = state.signatureEditId ? state.elements.find(e => e.id === state.signatureEditId) : null;
        $('#ec-signature-cert,#ec-signature-mask').val('');
        $('#ec-signature-password').val('');
        $('#ec-signature-mask-error').hide();
        $('#ec-signature-save').text(element ? 'Salvar' : 'Adicionar');
        showModal('#ec-signature-modal');
    }

    function saveSignature() {
        const certfile = document.getElementById('ec-signature-cert').files[0];
        const maskfile = document.getElementById('ec-signature-mask').files[0];
        const current = state.signatureEditId ? state.elements.find(e => e.id === state.signatureEditId) : null;
        if (!certfile && !current) return;
        if (!maskfile && (!current || !current.mask)) {
            $('#ec-signature-mask-error').show();
            $('#ec-signature-mask').trigger('focus');
            return;
        }
        $('#ec-signature-mask-error').hide();
        readFileAsDataURL(certfile, function(certdata) {
            readFileAsDataURL(maskfile, function(maskdata) {
                if (current) {
                    if (certdata) {
                        current.cert = certdata;
                    }
                    const password = $('#ec-signature-password').val();
                    if (certdata || password !== '') {
                        current.password = password;
                    }
                    if (maskdata) {
                        current.mask = maskdata;
                        current.src = maskdata;
                    }
                } else {
                    state.elements.push({id: uid('sig'), pageid: state.currentPage, type: 'signature', name: 'Assinatura ' + (state.elements.filter(function(item) { return item.type === 'signature' && item.pageid === state.currentPage; }).length + 1), cert: certdata, password: $('#ec-signature-password').val() || '', mask: maskdata, src: maskdata, x: 80, y: 80, w: 260, h: 90});
                }
                state.signatureEditId = null;
                $('#ec-signature-save').text('Adicionar');
                hideModal('#ec-signature-modal');
                render();
            });
        });
    }

    function getTextEditor() {
        if (window.tinyMCE && window.tinyMCE.get) {
            return window.tinyMCE.get('ec-text');
        }
        return null;
    }

    function normalizeHex(value) {
        value = String(value || '').trim();
        if (value.charAt(0) !== '#') {
            value = '#' + value;
        }
        if (!/^#[0-9a-fA-F]{6}$/.test(value)) {
            return '#111111';
        }
        return value.toUpperCase();
    }

    function applyEditorFormat(type, value) {
        const editor = getTextEditor();
        if (!editor) {
            return;
        }
        editor.focus();

        if (type === 'size') {
            editor.formatter.register('ecfontsize', {
                inline: 'span',
                styles: {fontSize: '%value'}
            });
            editor.formatter.apply('ecfontsize', {value: value});
        }

        if (type === 'color') {
            editor.formatter.register('eccolor', {
                inline: 'span',
                styles: {color: '%value'}
            });
            editor.formatter.apply('eccolor', {value: value});
        }

        editor.nodeChanged();
        editor.fire('change');
    }

    function bindInlineStyleControls() {
        let timer = null;
        const run = function(callback) {
            window.clearTimeout(timer);
            timer = window.setTimeout(callback, 120);
        };
        const applySize = function() {
            const size = Number($('#ec-size').val() || 0);
            if (size >= 8) {
                applyEditorFormat('size', size + 'px');
            }
        };
        const applyColor = function() {
            const color = normalizeHex($('#ec-color').val() || '#111111');
            $('#ec-color').val(color);
            applyEditorFormat('color', color);
        };
        $('#ec-size').on('input change', function() {
            run(applySize);
        });
        $('#ec-color').on('input change', function() {
            run(applyColor);
        });
    }

    function addBorder() {
        state.elements.push({id: uid('border'), pageid: state.currentPage, type: 'border', x: 40, y: 40, w: pageSize.w - 80, h: pageSize.h - 80});
        render();
    }

    function addPage() {
        const num = state.pages.length + 1;
        const page = {id: uid('p'), name: 'Página ' + num, background: '', width: pageSize.w, height: pageSize.h};
        state.pages.push(page);
        state.currentPage = page.id;
        render();
    }

    function removePage(id) {
        if (state.pages.length <= 1) {
            return;
        }
        const index = state.pages.findIndex(page => page.id === id);
        if (index < 0) {
            return;
        }
        state.pages.splice(index, 1);
        state.elements = state.elements.filter(element => element.pageid !== id);
        if (state.currentPage === id) {
            const nextpage = state.pages[Math.max(0, index - 1)] || state.pages[0];
            state.currentPage = nextpage.id;
        }
        if (state.selectedId && !state.elements.some(element => element.id === state.selectedId)) {
            state.selectedId = null;
        }
        syncHidden();
        render();
    }

    function preview() {
        const body = $('#ec-preview-body').empty();
        const bodyWidth = body.innerWidth() || pageSize.w;
        const scale = Math.min(1, Math.max(0.1, (bodyWidth - 8) / pageSize.w));
        state.pages.forEach(function(page) {
            const wrap = $('<div class="ec-preview-page-wrap"></div>').css({height: (pageSize.h * scale) + 'px'});
            const clone = $('<div class="ec-preview-page"></div>').css({
                width: pageSize.w + 'px',
                maxWidth: 'none',
                height: pageSize.h + 'px',
                transform: 'scale(' + scale + ')',
                backgroundImage: page.background ? 'url(' + page.background + ')' : 'none'
            });
            state.elements.filter(e => e.pageid === page.id).forEach(function(element) {
                const item = $('<div class="ec-preview-element"></div>').css({left: element.x, top: element.y, width: element.w, height: element.h});
                if (element.type === 'image' || element.type === 'signature') {
                    item.addClass(element.type === 'signature' ? 'ec-preview-signature' : 'ec-preview-image').append($('<img alt="">').attr('src', element.src || element.mask));
                } else if (element.type === 'border') {
                    item.addClass('ec-element-border');
                } else {
                    item.css({fontSize: (element.size || 24) + 'px', color: element.color || '#111', fontFamily: element.font || 'helvetica', fontWeight: element.bold ? '700' : '400', fontStyle: element.italic ? 'italic' : 'normal', textAlign: mapAlign(element.align || 'L')}).html(resolveText(element.text));
                }
                clone.append(item);
            });
            body.append(wrap.append(clone));
        });
        showModal('#ec-preview-modal');
    }

    function bind() {
        $('[data-ec-add]').on('click', function() {
            const type = $(this).data('ec-add');
            if (type === 'image') return openImage();
            if (type === 'signature') return openSignature();
            if (type === 'border') return addBorder();
            openField(type);
        });
        $('#ec-field-save').on('click', saveField);
        $('#ec-image-save').on('click', saveImage);
        $('#ec-signature-mask').on('change', function() { $('#ec-signature-mask-error').hide(); });
        $('#ec-signature-save').on('click', saveSignature);
        $('#ec-rename-save').on('click', saveRename);
        $('#ec-rename-name').on('keydown', function(ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                saveRename();
            }
        });
        bindInlineStyleControls();
        $('.ec-info-toggle').on('click', function() { $('.ec-info-content').toggle(); });
        $('#ec-add-page').on('click', addPage);
        $('#ec-stage-wrap').on('click', function(ev) {
            if ($(ev.target).closest('.ec-element').length) {
                return;
            }
            state.selectedId = null;
            render();
        });
        $(document).on('click.easycertificate', function(ev) {
            if ($(ev.target).closest('.ec-element, .modal, .ec-toolbar, .ec-actions, .ec-items-panel').length) {
                return;
            }
            if (state.selectedId) {
                state.selectedId = null;
                render();
            }
        });
        $('#ec-zoom-in').on('click', function() { state.zoom = Math.min(2, state.zoom + 0.1); render(); });
        $('#ec-zoom-out').on('click', function() { state.zoom = Math.max(0.4, state.zoom - 0.1); render(); });
        $('.easycertificate-template-form').on('submit', syncHidden);
        $('#ec-userfield-select').on('change', function() {
            setTextEditorValue('{' + $(this).val() + '}');
        });
        $('#ec-datefield-select').on('change', function() {
            setTextEditorValue('{' + $(this).val() + '}');
        });
        $('#ec-concatfield-select').on('change', function() {
            const value = $(this).val();
            if (value) {
                appendTextEditorValue('{' + value + '}');
                $(this).val('');
            }
        });
        $('#ec-customfield').on('input', function() {
            const value = $(this).val().trim().replace(/[{}]/g, '');
            if (value) setTextEditorValue('{' + value + '}');
        });
    }

    return {
        init: function(config) {
            state.config = config || {};
            state.pages = Array.isArray(config.pages) && config.pages.length ? config.pages : [{id: 'p1', name: 'Página 1', background: '', width: pageSize.w, height: pageSize.h}];
            state.elements = Array.isArray(config.elements) ? config.elements : [];
            state.currentPage = state.pages[0].id;
            fillSelects();
            bind();
            render();
        }
    };
});
