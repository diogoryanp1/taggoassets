import './bootstrap';
import Alpine from 'alpinejs';

window.assetForm = (config) => ({
    endpoints: config.endpoints,
    selected: config.selected,
    customValues: config.customValues || {},
    types: [],
    models: [],
    locations: [],
    fields: [],
    loading: { types: false, models: false, locations: false, fields: false },
    errors: { types: '', models: '', locations: '', fields: '' },
    requestSeq: { types: 0, models: 0, locations: 0, fields: 0 },
    init() {
        if (this.selected.category) {
            this.loadTypes(false);
            this.loadFields();
        }
        if (this.selected.brand) {
            this.loadModels(false);
        }
        if (this.selected.organizationalUnit) {
            this.loadLocations(false);
        }
    },
    async fetchOptions(key, url, params = {}) {
        const token = ++this.requestSeq[key];
        this.loading[key] = true;
        this.errors[key] = '';
        try {
            const response = await fetch(`${url}?${new URLSearchParams(params)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Nao foi possivel carregar as opcoes.');
            const payload = await response.json();
            return token === this.requestSeq[key] ? payload : null;
        } catch (error) {
            this.errors[key] = error.message;
            return [];
        } finally {
            this.loading[key] = false;
        }
    },
    async loadTypes(clear = true) {
        if (clear) this.selected.type = '';
        this.types = [];
        if (!this.selected.category) return;
        const payload = await this.fetchOptions('types', this.endpoints.types, { category: this.selected.category });
        if (payload !== null) this.types = payload;
    },
    async loadModels(clear = true) {
        if (clear) this.selected.model = '';
        this.models = [];
        if (!this.selected.brand) return;
        const payload = await this.fetchOptions('models', this.endpoints.models, { brand: this.selected.brand, type: this.selected.type || '' });
        if (payload !== null) this.models = payload;
    },
    async loadLocations(clear = true) {
        if (clear) this.selected.location = '';
        this.locations = [];
        if (!this.selected.organizationalUnit) return;
        const payload = await this.fetchOptions('locations', this.endpoints.locations, { unit: this.selected.organizationalUnit });
        if (payload !== null) this.locations = payload;
    },
    async loadFields() {
        this.fields = [];
        if (!this.selected.category) return;
        const payload = await this.fetchOptions('fields', this.endpoints.fields, { category: this.selected.category });
        if (payload !== null) this.fields = payload;
    },
});

window.Alpine = Alpine;
Alpine.start();
