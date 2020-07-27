var evolvedcookie = Class.create({
    afterInit: function() {
        this.addLinkListener();
        this.addSelectListener();
        this.addSearchListener();
        this.clearCookie();
    },
    addLinkListener: function() {
        document.observe("click", function(e) {
            var target = e.findElement("a");
            if (target && target.tagName.toLowerCase() == "a" && target.href && (target.href.indexOf("#") == -1 || target.href.indexOf("#") > 0) && target.href.indexOf("javascript") == -1) {
                Event.stop(e);
                var area = target.readAttribute("evolved_area");
                var linkstore = target.readAttribute("evolved_store");
                var excluded = target.readAttribute("evolved_excluded");
                var urltarget = target.readAttribute("target");
                this.forwardRequest(target.href, area, false, urltarget, linkstore, excluded);
            }
        }.bindAsEventListener(this));
    },
    addSelectListener: function() {
        if (navigator.userAgent.indexOf("MSIE 7.0") == -1) {
            $$("select").each(function(e) {
                var action = e.readAttribute("onchange");
                if (action) {
                    action = action.toLowerCase();
                    if (action.indexOf("location") != -1) {
                        e.writeAttribute("onchange", "thisevolvedcookie.processSelect(this)");
                    }
                }
            }.bind(this));
        }
    },
    clearCookie: function() {
        var date = new Date();
        date.setDate(date.getDate() - 1);
        document.cookie = "evolved_key=; expires=" + date.toUTCString() + "; domain=" + window.location.hostname + "; path=/";
    },
    processSelect: function(e) {
        var value = e.value;
        var area = e.down("option[selected]").readAttribute("evolved_area");
        var linkstore = e.down("option[selected]").readAttribute("evolved_store");
        var excluded = e.down("option[selected]").readAttribute("evolved_excluded");
        var urltarget = e.down("option[selected]").readAttribute("target");
        this.forwardRequest(value, area, false, urltarget, linkstore, excluded);
    },
    addSearchListener: function() {
        if ($("search")) {
            $("search").up("form").observe("submit", function(e) {
                Event.stop(e);
                var target = e.target;
                var arg = $("search").value;
                arg = escape(arg);
                var requrl = target.action + "?q=" + arg;
                this.forwardRequest(requrl, "category", true);
                target.submit();
            }.bindAsEventListener(this));
        }
    },
    forwardRequest: function(requrl, area, issearch, urltarget, linkstore, excluded) {
        if (area) {
            var args = requrl.split("?");
            if (args.length > 1) {
                args = args[1];
                var args = args.toQueryParams();
                if (typeof(args) != "object") {
                    args = false;
                } else {
                    $H(args).each(function (e) {
                        if (e.key == "q" && $("search")) {
                            args[e.key] = $("search").value;
                        }
                    }.bind(this));
                }
            } else {
                args = {};
            }

            var key = this.buildKey(requrl, args, area, linkstore);
            if (this.usearea) {
                key = area + "_" + key;
            }
            
            if (!excluded) {
                var date = new Date();
                date.setDate(date.getDate() + 1);
                document.cookie = "evolved_key=" + key + "; expires=" + date.toUTCString() + "; domain=" + window.location.hostname + "; path=/";
            }
        }
        
        if (!issearch) {
            if (urltarget == "_blank") {
                window.open(requrl);
            } else {
                window.location.href = requrl;
            }
        }
    },
    buildKey: function(requrl, args, area, linkstore) {
        var store = this.getStore(args, linkstore);
        var protocol = this.getProtocol(requrl);
        var agent = this.getAgent(args);
        var currency = this.getCurrency(requrl);
        var category = this.getCategory(args, area);
        var layered = this.getLayered(args, area);
        var tax = this.getTax();
        var request = this.getRequest(requrl);
        return hex_md5(protocol + "_" + store + "_" + currency + "_" + agent + "_" + category + "_" + layered + "_" + tax + "_" + request);
    },
    getStore: function(args, linkstore) {
        if (linkstore) {
            return linkstore;
        } else if (args["___store"]) {
            return args["___store"];
        }
        return this.modifiers.___store;
    },
    getProtocol: function(requrl) {
        if (requrl.indexOf("http://") != -1) {
            return "http";
        } else if (requrl.indexOf("https://") != -1) {
            return "https";
        }
        return this.modifiers.protocol;
    },
    getAgent: function(args) {
        if (args["___store"]) {
            var store = args["___store"];
            return this.layeredkeys[store];
        } else {
            return this.modifiers.agent;
        }
    },
    getCurrency: function(requrl) {
        if (requrl.indexOf("directory/currency/switch/currency/") != -1) {
            var currency = requrl.replace(/.*directory\/currency\/switch\/currency\//, "");
            currency = currency.split("/");
            currency = currency.shift();
            return currency;
        }
        return this.modifiers.currency;
    },
    getCategory: function(args, area) {
        if (area == "category") {
            var dir = args["dir"] ? args["dir"] : this.modifiers.dir;
            var limit = args["limit"] ? args["limit"] : this.modifiers.limit;
            var mode = args["mode"] ? args["mode"] : this.modifiers.mode;
            var order = args["order"] ? args["order"] : this.modifiers.order;
            var p = args["p"] ? args["p"] : "1";
            return hex_md5(dir + limit + mode + order + p);
        } else {
            return hex_md5("");
        }
    },
    getLayered: function(args, area) {
        var layered = {}
        $H(args).each(function (e) {
            if (area == "other") {
                if (e.key != "___store" && e.key != "___from_store" && e.key != "___SID" && e.key != "SID" && e.value) {
                    layered[e.key] = e.value;
                }
            } else {
                if (e.key != "mode" && e.key != "dir" && e.key != "order" && e.key != "limit" && e.key != "p" && e.key != "___store" && e.key != "___from_store" && e.key != "___SID" && e.key != "SID" && e.value) {
                    layered[e.key] = e.value;
                }
            }
        }.bind(this));
        layered = this.sortByKey(layered);
        var modifier = "";
        $H(layered).each(function (e) {
            modifier += e.key + e.value;
        }.bind(this));
        return hex_md5(modifier);
    },
    getTax: function() {
        if (!this.tax) {
            var cookies = document.cookie.split(/;\s*/);
            for (var i = 0; i < cookies.length; i++)
            {
                if (cookies[i].indexOf("evolved_tax") == 0) {
                    var tax = cookies[i].split("=");
                    this.tax = tax.pop();
                }
            }
        }
        return this.tax;
    },
    sortByKey: function(array) {
        var keys = [];
        $H(array).each(function (e) {
            keys.push(e.key);
        }.bind(this));
        if (keys.length) {
            keys.sort();
            var sorted = {};
            keys.each(function (e) {
                sorted[e] = array[e];
            }.bind(this));
            return sorted
        } else {
            return array;
        }
    },
    getRequest: function(requrl) {
        var url = requrl.replace(/http(s)?:\/\/.*?\//, "/");
        url = url.split("?");
        url = url[0];
        return url.replace(/\/*$/, "");
    }
});

var evolvedupdate = Class.create({
    afterInit: function() {
        if (this.excludedpage) {
            this.fadeElements();
            $$(".evolved_class").each(function(el) {
                this.updateSingleElement(el);
            }.bind(this));
            this.fadeElements();
            this.showElements();
        } else if (this.useajax) {
            this.fadeElements();
            this.getBlockContent();
        } else {
            document.fire.bind(document).defer("evolved:loaded");
        }
    },
    fadeElements: function() {
        $$(".evolved_class").each(function(el) {
            this.fadeSingleElement(el);
        }.bind(this));
    },
    fadeSingleElement: function(el) {
        if (!el.hasClassName("evolved_holding")) {
            el.hide();
            if (navigator.userAgent.indexOf("MSIE") == -1) {
                el.setStyle("opacity: 0");
            }
        }
    },
    getBlockContent: function() {
        var url = window.location.href;
        var args = url.split("?");
        var arg = false;
        if (args.length > 1) {
            args = args[1];
            var args = args.toQueryParams();
            if (args['cacheexclude']) {
                return;
            }
            $H(args).each(function(e) {
                if (e.key == 'evolved') {
                    arg = true;
                    throw $break;
                }
            }.bind(this));
        }
        
        var parameters = this.blocks;
        
        if (this.price) {
            $$("div.evolved_price").each(function(e) {
                var block = e.className.match(/eprice_[0-9_]+/);
                if (block) {
                    parameters[block] = block;
                }
            }.bind(this));
        }
        if (this.creview) {
            $$("div.evolved_creview").each(function(e) {
                var block = e.className.match(/creview_[0-9_]+/);
                if (block) {
                    parameters[block] = block;
                }
            }.bind(this));
        }
        if (this.cart) {
            $$("div.evolved_cart").each(function(e) {
                var block = e.className.match(/ecart_[0-9_]+/);
                if (block) {
                    parameters[block] = block;
                }
            }.bind(this));
        }
        if (this.preview) {
            $$("div.evolved_preview").each(function(e) {
                var block = e.className.match(/preview_[0-9_]+/);
                if (block) {
                    parameters[block] = block;
                }
            }.bind(this));
        }
        if (this.tier) {
            $$("div.evolved_id-tier").each(function(e) {
                parameters['tier'] = 'tier';
            }.bind(this));
        }
        if (this.welcome) {
            $$("div.evolved_id-welcome").each(function(e) {
                parameters['welcome'] = 'welcome';
            }.bind(this));
        }
        
        parameters.evolvedupdate = true;
        parameters.id = this.isreview;
        if (arg) {
            parameters.evolved = true;
        }
        new Ajax.Request(url, {
            parameters: parameters,
            onSuccess: function(response) {
                var contentarray = response.responseText.evalJSON();
                if (typeof(contentarray) == "object") {
                    var blocks = contentarray.content;
                    var showelements = contentarray.showelements ? contentarray.showelements : false;
                    var formkey = contentarray.formkey;
                    if (formkey) {
                        var date = new Date();
                        date.setSeconds(date.getSeconds() + parseInt(this.cookieexpiration));
                        document.cookie = "evolved_formkey=" + formkey + "; expires=" + date.toUTCString() + "; domain=" + window.location.hostname + "; path=/";
                    }
                    this.updatePage(blocks, showelements);
                }
                this.showElements();
            }.bind(this),
            onFailure: function(response) {
                this.showElements();
            }.bind(this)
        });
    },
    updatePage: function(blocks, showelements) {
        $H(blocks).each(function(e) {
            var id = e.key.replace(/[^A-Za-z0-9]{1}/g, "_");
            $$("div.evolved_id-" + id).each(function(el) {
                this.updateSingleElement(el, e.value, showelements);
            }.bind(this));
        }.bind(this));
        this.fadeElements();
        $$(".evolved_class").each(function(e) {
            if (showelements) {
                var id = e.className;
                id = id.match(/evolved_id-([a-zA-Z0-9_]+)/);
                id = id[1];
                $H(blocks).each(function(e) {
                    var sanitised = e.key.replace(/[^A-Za-z0-9]{1}/g, "_");
                    if (id == sanitised) {
                        id = e.key;
                        throw $break;
                    }
                }.bind(this));
                e.setStyle("border:1px solid #f00");
                e.insert({
                    top : "<div style=\"float:left; color:#f00; font-weight:bold; font-size:12px; margin:5px; padding:3px 5px; border:1px solid #000; background-color:#fff; text-transform:initial\">" + id + "</div>"
                });
                e.insert({
                    bottom : "<div style=\"clear:both\"></div>"
                });
            }
            if (this.slidespeed && !showelements) {
                e.insert({
                    bottom : "<div style=\"position:relative; bottom:0\"></div>"
                });
            }
        }.bind(this));
    },
    updateSingleElement: function(el, html, showelements) {
        if (html) {
            el.update(html);
        }
        if (!showelements) {
            this.styleChildren(el);
            this.removeParent(el);
        }
    },
    styleChildren: function(el) {
        var children = el.childElements();
        children.each(function(e) {
            if (!e.hasClassName("evolved_class")) {
                e.addClassName("evolved_class");
                if (!this.useajax) {
                    if (!e.hasClassName("evolved_flush")) {
                        e.addClassName("evolved_flush");
                    }
                }
            }
            if (!e.hasClassName("evolved_holding") && el.hasClassName("evolved_holding")) {
                e.addClassName("evolved_holding");
            }
        }.bind(this));
    },
    removeParent: function(el) {
        var html = el.innerHTML;
        el.insert({ before : html });
        el.remove();
    },
    showElements: function() {
        $$(".evolved_class").each(function(el) {
            this.showSingleElement(el);
        }.bind(this));
        document.fire.bind(document).defer("evolved:loaded");
    },
    showSingleElement: function(el) {
        if (!el.hasClassName("evolved_holding")) {
            el.fade({
                duration: this.fadespeed,
                from: 0,
                to: 1
            });
            if (this.slidespeed) {
                Effect.SlideDown(el, {
                    duration : this.slidespeed
                });
            }
            el.show();
        }
    },
    showSingleBlockContent: function(id, html, showelements) {
        var el = $$("div.evolved_id-" + id)[0];
        if (el) {
            this.updateSingleElement(el, html, showelements);
            $$(".evolved_flush").each(function(el) {
                this.showSingleElement(el);
                el.removeClassName("evolved_flush");
            }.bind(this));
        }
    },
    showFlushContent: function() {
        var run = false;
        $$(".evolved_class").each(function(el) {
            if (el.className.match(/evolved_id-/)) {
                run = true;
            }
            if (el.hasClassName("evolved_flush")) {
                el.removeClassName("evolved_flush");
            }
        }.bind(this));
        if (run) {
            this.fadeElements();
            this.getBlockContent();
        }
    }
});

document.observe("dom:loaded", function() {
    if (typeof(thisevolvedcookie) == "object") {
        thisevolvedcookie.afterInit();
    }
    if (typeof(thisevolvedupdate) == "object") {
        thisevolvedupdate.afterInit();
    } else {
        document.fire.bind(document).defer("evolved:loaded");
    }
});