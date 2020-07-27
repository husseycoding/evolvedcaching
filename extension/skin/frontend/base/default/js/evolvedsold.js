var evolvedsold = Class.create({
    updateProducts: function() {
        new Ajax.Request(this.url, {
            parameters: {ids: this.ids}
        });
    }
});

document.observe("dom:loaded", function() {
    if (typeof(thisevolvedsold) == "object") {
        thisevolvedsold.updateProducts();
    }
});