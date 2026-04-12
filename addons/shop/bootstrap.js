require.config({
    paths: {
        'vue': '../addons/shop/js/vue.min',
        'jquery-colorpicker': '../addons/shop/js/jquery.colorpicker.min',
    },
    shim: {
        'jquery-colorpicker': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        }
    }
});
