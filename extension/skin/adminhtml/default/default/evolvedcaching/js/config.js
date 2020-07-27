var config = Class.create({
    initialize: function() {
        if ($("evolvedcaching_storage_use")) {
            Event.observe($("evolvedcaching_storage_use"), "change", this.setConfigVisibility.bind(this));
            this.setConfigVisibility();
            this.hideGuideScope();
        }
    },
    setConfigVisibility: function() {
        if ($("evolvedcaching_storage_use").value == "0") {
            this.hideApc();
            this.hideMemcached();
            this.hideRedis();
        } else if ($("evolvedcaching_storage_use").value == "1") {
            this.showMemcached();
            this.hideApc();
            this.hideRedis();
        } else if ($("evolvedcaching_storage_use").value == "2") {
            this.showApc();
            this.hideMemcached();
            this.hideRedis();
        } else if ($("evolvedcaching_storage_use").value == "3") {
            this.showRedis();
            this.hideMemcached();
            this.hideApc();
        }
    },
    hideApc: function() {
        $("row_evolvedcaching_storage_comment1").hide();
        $("row_evolvedcaching_storage_apcexpires").hide();
    },
    showApc: function() {
        $("row_evolvedcaching_storage_comment1").show();
        $("row_evolvedcaching_storage_apcexpires").show();
    },
    hideMemcached: function() {
        $("row_evolvedcaching_storage_comment2").hide();
        $("row_evolvedcaching_storage_host").hide();
        $("row_evolvedcaching_storage_port").hide();
        $("row_evolvedcaching_storage_persistent").hide();
        $("row_evolvedcaching_storage_compression").hide();
        $("row_evolvedcaching_storage_threshold").hide();
        $("row_evolvedcaching_storage_saving").hide();
        $("row_evolvedcaching_storage_memcachedexpires").hide();
    },
    showMemcached: function() {
        $("row_evolvedcaching_storage_comment2").show();
        $("row_evolvedcaching_storage_host").show();
        $("row_evolvedcaching_storage_port").show();
        $("row_evolvedcaching_storage_persistent").show();
        $("row_evolvedcaching_storage_compression").show();
        $("row_evolvedcaching_storage_threshold").show();
        $("row_evolvedcaching_storage_saving").show();
        $("row_evolvedcaching_storage_memcachedexpires").show();
    },
    hideRedis: function() {
        $("row_evolvedcaching_storage_comment3").hide();
        $("row_evolvedcaching_storage_rhost").hide();
        $("row_evolvedcaching_storage_rport").hide();
        $("row_evolvedcaching_storage_rtimeout").hide();
        $("row_evolvedcaching_storage_rpersistence").hide();
        $("row_evolvedcaching_storage_rdatabase").hide();
        $("row_evolvedcaching_storage_rpassword").hide();
    },
    showRedis: function() {
        $("row_evolvedcaching_storage_comment3").show();
        $("row_evolvedcaching_storage_rhost").show();
        $("row_evolvedcaching_storage_rport").show();
        $("row_evolvedcaching_storage_rtimeout").show();
        $("row_evolvedcaching_storage_rpersistence").show();
        $("row_evolvedcaching_storage_rdatabase").show();
        $("row_evolvedcaching_storage_rpassword").show();
    },
    hideGuideScope: function() {
        $$(".scope-label").each(function(e) {
            if (e.previous().down()) {
                var tag = e.previous().down().tagName.toLowerCase();
                if (tag != "input" && tag != "select" && tag != "textarea") {
                    e.hide();
                }
            } else {
                e.hide();
            }
        }.bind(this));
        $$(".use-default").each(function(e) {
            if (e.previous().down()) {
                var tag = e.previous().down().tagName.toLowerCase();
                if (tag != "input" && tag != "select" && tag != "textarea") {
                    e.hide();
                    e.next().hide();
                }
            } else {
                e.hide();
                e.next().hide();
            }
        }.bind(this));
    }
});

document.observe("dom:loaded", function() {
    new config();
});