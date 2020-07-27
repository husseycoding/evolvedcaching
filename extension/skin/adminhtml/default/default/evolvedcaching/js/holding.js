var holding = Class.create({
    initialize: function() {
        if ($("evolvedcaching_exclude_blocks")) {
            $("row_evolvedcaching_exclude_blocks_holding").hide();
            $("row_evolvedcaching_exclude_blocks_html").hide();
            this.addDynamicListeners();
            this.currentblock = false;
            Event.observe($("evolvedcaching_exclude_blocks"), "keyup", this.showExcluded.bind(this));
            Event.observe($("evolvedcaching_exclude_blocks_html"), "keyup", this.updateHolding.bind(this, false));
            $("row_evolvedcaching_exclude_blocks").insert({ after : "<tr><td class=\"label\"></td><td id=\"displayexcludedblocks\" class=\"value\"></td></tr>" });
            this.showExcluded();
            this.getCurrentHoldingHtml();
        }
    },
    setDynamicVisibility: function(e) {
        if (e.target.value != "1") {
            $("row_" + e.target.id + "_holding").hide();
        } else {
            $("row_" + e.target.id + "_holding").show();
        }
    },
    addDynamicListeners: function() {
        if ($("evolvedcaching_exclude_price").value != "1") {
            $("row_evolvedcaching_exclude_price_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_price"), "change", this.setDynamicVisibility.bind(this));
        
        if ($("evolvedcaching_exclude_creview").value != "1") {
            $("row_evolvedcaching_exclude_creview_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_creview"), "change", this.setDynamicVisibility.bind(this));
        
        if ($("evolvedcaching_exclude_cart").value != "1") {
            $("row_evolvedcaching_exclude_cart_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_cart"), "change", this.setDynamicVisibility.bind(this));
        
        if ($("evolvedcaching_exclude_preview").value != "1") {
            $("row_evolvedcaching_exclude_preview_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_preview"), "change", this.setDynamicVisibility.bind(this));
        
        if ($("evolvedcaching_exclude_tier").value != "1") {
            $("row_evolvedcaching_exclude_tier_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_tier"), "change", this.setDynamicVisibility.bind(this));
        
        if ($("evolvedcaching_exclude_welcome").value != "1") {
            $("row_evolvedcaching_exclude_welcome_holding").hide();
        }
        Event.observe($("evolvedcaching_exclude_welcome"), "change", this.setDynamicVisibility.bind(this));
    },
    showExcluded: function() {
        var blocks = this.getBlocks();
        if (blocks) {
            var html = "";
            var listeners = new Array();
            blocks.each(function(e) {
                html += e + " - <a href=\"javascript:void(0)\" id=\"set_" + e + "\">Set Holding HTML</a><br />";
                listeners.push(e);
            }.bind(this));
            $("displayexcludedblocks").update(html);
            listeners.each(function(e) {
                Event.observe($("set_" + e), "click", this.setHolding.bind(this, e));
            }.bind(this));
        } else {
            $("displayexcludedblocks").update();
        }
    },
    getBlocks: function() {
        var blocks = $("evolvedcaching_exclude_blocks").value;
        if (blocks) {
            blocks = blocks.replace(/\s/g, "");
            blocks = blocks.split(",");
            return blocks;
        }
        return false;
    },
    setHolding: function(block, e) {
        this.currentblock = block;
        if (this.holdinghtml[block]) {
            $("evolvedcaching_exclude_blocks_html").value = this.holdinghtml[block];
        } else {
            $("evolvedcaching_exclude_blocks_html").value = "";
        }
        $("row_evolvedcaching_exclude_blocks_html").down("label").update("Block Holding HTML<br />" + "(" + block + ")");
        $("row_evolvedcaching_exclude_blocks_html").show();
    },
    updateHolding: function(block, e) {
        if (!block) {
            block = this.currentblock;
        }
        this.holdinghtml[block] = e.target.value;
        $("evolvedcaching_exclude_blocks_holding").value = Object.toJSON(this.holdinghtml);
    },
    getCurrentHoldingHtml: function() {
        if ($("evolvedcaching_exclude_blocks_holding").value) {
            this.holdinghtml = $("evolvedcaching_exclude_blocks_holding").value.evalJSON();
        } else {
            this.holdinghtml = {};
        }
    }
});

document.observe("dom:loaded", function() {
    new holding();
});