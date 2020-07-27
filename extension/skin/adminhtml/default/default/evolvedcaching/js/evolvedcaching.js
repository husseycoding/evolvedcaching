var styleexpired = Class.create({
    initialize: function() {
        $$("td.expired").each(function(e) {
            if (e.innerHTML.replace(/\s/g, "").toLowerCase() == "yes") {
                e.up("tr").addClassName("expired");
            }
        });
        $$("h3.head-adminhtml-evolvedcaching")[0].insert({
            after: "<p class=\"expired_comment icon-head\">Cached pages can expire over time or be deleted, these pages are shown faded out.  Viewing a page on the frontend will recreate it or you can remove or rebuild expired pages, and rebuild the existing cache with the refresh button.</p>"
        });
    }
});

var refreshentries = Class.create({
    afterInit: function() {
        this.completed = 0;
        this.running = false;
        this.sendtotal = true;
        $$("td.form-buttons")[0].insert({
            bottom: "<div id=\"refresh_count_target\"></div>"
        });
        this.report = $("refresh_count_target");
        this.handler = false;
        this.lastid = false;
    },
    confirmRefresh: function() {
        if (this.running) {
            alert("Cache entries are still being refreshed!")
        } else if (confirm("This may take some time, are you sure?")) {
            this.handler = this.leaveNotice.bind(this);
            Event.observe(window, 'beforeunload', this.handler);
            this.running = true;
            $("messages").update();
            this.sendAjax()
        }
    },
    leaveNotice: function(ev) {
        if (this.running) {
            ev.returnValue = "Cache entries are still being refreshed!";
        }
    },
    sendAjax: function(type) {
        var parameters = {evolvedrefresh : true, lastcount : this.completed, totalcount : this.total, sendtotal : this.sendtotal, type : type};
        if (this.lastid) {
            parameters['lastid'] = this.lastid;
        }
        new Ajax.Request(this.refreshurl, {
            parameters: parameters,
            onSuccess: function(response) {
                var contentarray = response.responseText.evalJSON();
                var completed = parseInt(contentarray.completed);
                var total = parseInt(contentarray.total);
                var lastid = parseInt(contentarray.lastid);
                if (lastid) {
                    this.lastid = lastid;
                }
                if (total) {
                    this.total = total;
                    this.sendtotal = false;
                }
                if (completed) {
                    this.completed = completed;
                    if (this.total <= this.completed) {
                        this.report.update("Done.  " + this.completed + " entries refreshed");
                        this.completed = 0;
                        Event.stopObserving(window, "beforeunload", this.handler);
                        this.running = false;
                        this.sendtotal = true;
                        this.lastid = false;
                        setLocation(this.reloadurl);
                    } else if (!this.total) {
                        this.report.update("No entries to refresh");
                        this.running = false;
                        this.sendtotal = true;
                        this.lastid = false;
                    } else {
                        this.report.update(this.completed + " of " + this.total + " entries refreshed");
                        this.sendAjax();
                    }
                } else {
                    this.report.update("Failed!  " + this.completed + " of " + this.total + " entries refreshed");
                    this.completed = 0;
                    Event.stopObserving(window, "beforeunload", this.handler);
                    this.running = false;
                    this.sendtotal = true;
                    this.lastid = false;
                }
            }.bind(this)
        });
    }
});

var crawler = Class.create({
    afterInit: function() {
        this.completed = 0;
        this.running = false;
        this.sendtotal = true;
        $$("td.form-buttons")[0].insert({
            bottom: "<div id=\"crawl_count_target\"></div>"
        });
        this.report = $("crawl_count_target");
        this.handler = false;
    },
    confirmCrawl: function() {
        if (this.running) {
            alert("Pages are still being crawled!")
        } else if (confirm("This may take some time, are you sure?")) {
            this.report.update("Building URL list");
            this.handler = this.leaveNotice.bind(this);
            Event.observe(window, 'beforeunload', this.handler);
            this.running = true;
            $("messages").update();
            this.sendAjax()
        }
    },
    leaveNotice: function(ev) {
        if (this.running) {
            ev.returnValue = "Pages are still being crawled!";
        }
    },
    sendAjax: function() {
        var parameters = {evolvedcrawler : true, totalcount : this.total, sendtotal : this.sendtotal};
        new Ajax.Request(this.crawlurl, {
            parameters: parameters,
            onSuccess: function(response) {
                var contentarray = response.responseText.evalJSON();
                var completed = parseInt(contentarray.completed);
                var total = parseInt(contentarray.total);
                if (total) {
                    this.total = total;
                    this.sendtotal = false;
                }
                if (completed) {
                    this.completed = completed;
                    if (this.total <= this.completed) {
                        this.report.update("Done.  " + this.completed + " pages crawled");
                        this.completed = 0;
                        Event.stopObserving(window, "beforeunload", this.handler);
                        this.running = false;
                        this.sendtotal = true;
                        setLocation(this.reloadurl);
                    } else if (!this.total) {
                        this.report.update("No pages to generate");
                        this.running = false;
                        this.sendtotal = true;
                    } else {
                        this.report.update(this.completed + " of " + this.total + " crawled");
                        this.sendAjax();
                    }
                } else {
                    this.report.update("Failed!  " + this.completed + " of " + this.total + " pages crawled");
                    this.completed = 0;
                    Event.stopObserving(window, "beforeunload", this.handler);
                    this.running = false;
                    this.sendtotal = true;
                }
            }.bind(this)
        });
    }
});

var viewcache = Class.create({
    view: function(el) {
        var row = el.up("tr");
        if (!row.hasClassName("expired")) {
            var id = row.down("input.massaction-checkbox").value;
            if (id) {
                var url = this.viewcacheurl + "id/" + id + "?cacheexclude=1";
                window.open(url, "_blank");
            }
        }
    }
});

document.observe("dom:loaded", function() {
    var thisstyleexpired = new styleexpired();
    if (typeof(thisrefreshentries) == "object") {
        thisrefreshentries.afterInit();
    }
    if (typeof(thiscrawler) == "object") {
        thiscrawler.afterInit();
    }
});