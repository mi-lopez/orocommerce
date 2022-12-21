import BaseComponent from 'oroui/js/app/components/base/component';
import loadModules from 'oroui/js/app/services/load-modules';
import GrapesjsEditorView from './grapesjs-editor-view';

const GrapesjsEditorComponent = BaseComponent.extend({
    defaultModules: [
        'orocms/js/app/grapesjs/plugins/component-types',
        'orocms/js/app/grapesjs/plugins/components/grapesjs-components',
        'orocms/js/app/grapesjs/plugins/grapesjs-style-isolation',
        'orocms/js/app/grapesjs/plugins/import',
        'orocms/js/app/grapesjs/plugins/export',
        'orocms/js/app/grapesjs/plugins/code',
        'orocms/js/app/grapesjs/plugins/panel-scrolling-hints',
        'orocms/js/app/grapesjs/plugins/components/sorter-hints',
        'orocms/js/app/grapesjs/plugins/code-mode'
    ],

    constructor: function GrapesjsEditorComponent(...args) {
        GrapesjsEditorComponent.__super__.constructor.apply(this, args);
    },

    initialize({_sourceElement, jsmodules, builderPlugins, ...options} = {}) {
        const initializeView = this.initializeView.bind(this, {
            ...options,
            builderPlugins,
            el: _sourceElement
        });

        this._deferredInit();

        loadModules(this.prepareModules({
            jsmodules,
            builderPlugins
        })).then(initializeView);
    },

    prepareModules({jsmodules = [], builderPlugins = {}}) {
        const pluginJSModules = Object.values(builderPlugins)
            .filter(({jsmodule}) => jsmodule)
            .map(({jsmodule}) => jsmodule);

        return [...this.defaultModules, ...jsmodules, ...pluginJSModules];
    },

    initializeView(options) {
        if (this.disposed) {
            this._resolveDeferredInit();
            return;
        }

        this.view = new GrapesjsEditorView(options);
        this._resolveDeferredInit();
    }
});

export default GrapesjsEditorComponent;
