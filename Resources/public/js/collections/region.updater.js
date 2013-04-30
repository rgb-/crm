Oro = Oro || {};
Oro.RegionUpdater = Oro.RegionUpdater || {};

Oro.RegionUpdater.Collection = Backbone.Collection.extend({
    route: 'oro_api_country_get_regions',
    url: null,
    model: Oro.RegionUpdater.Region,

    initialize: function () {
        this.url = Routing.generate(this.route);
    },

    setCountryId: function (id) {
        this.url = Routing.generate(this.route, {id: id});
    }
});
