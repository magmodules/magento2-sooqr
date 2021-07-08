define([
        'jquery',
        'uiComponent',
    ], function ($, Component) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
                $.ajax({
                    url: this.sooqrheaddata.url,
                    type: 'post',
                    success: function (data) {
                        $('head').append(data);
                    }
                });
            },
        });
    }
);