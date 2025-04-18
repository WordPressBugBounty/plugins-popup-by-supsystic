jQuery.fn.nextInArray = function(b) {
    var c = 0;
    for (var a = 0; a < this.length; a++) {
        if (this[a] == b) {
            c = a + 1;
            break
        }
    }
    if (c > this.length - 1) {
        c = 0
    }
    return this[c]
};
jQuery.fn.clearForm = function() {
    return this.each(function() {
        var b = this.type,
            a = this.tagName.toLowerCase();
        if (a == "form") {
            return jQuery(":input", this).clearForm()
        }
        if (b == "text" || b == "password" || a == "textarea") {
            this.value = ""
        } else {
            if (b == "checkbox" || b == "radio") {
                this.checked = false
            } else {
                if (a == "select") {
                    this.selectedIndex = -1
                }
            }
        }
    })
};
jQuery.fn.tagName = function() {
    return this.get(0).tagName
};
jQuery.fn.exists = function() {
    return (jQuery(this).length > 0 ? true : false)
};

function isNumber(a) {
    return /^\d+/.test(a)
}

function pushDataToParam(e, a) {
    a = a ? a : "";
    var d = [];
    for (var c in e) {
        var b = a && a != "" ? a + "[" + c + "]" : c;
        if (typeof(e[c]) === "array" || typeof(e[c]) === "object") {
            d = jQuery.merge(d, pushDataToParam(e[c], b))
        } else {
            d.push(b + "=" + e[c])
        }
    }
    return d
}
jQuery.fn.serializeAnythingPps = function(d, b) {
    var c = b ? {} : [],
        a = jQuery(this).find(":input").get();
    jQuery.each(a, function() {
        if (this.name && !this.disabled && (this.checked || /select|textarea/i.test(this.nodeName) || /text|hidden|password/i.test(this.type))) {
            var e = jQuery(this).val();
            if (b) {
                c[this.name] = e
            } else {
                c.push(encodeURIComponent(this.name) + "=" + encodeURIComponent(e))
            }
        }
    });
    if (typeof(d) != "undefined" && d) {
        c = jQuery.merge(c, pushDataToParam(d))
    }
    return b ? c : c.join("&").replace(/%20/g, "+")
};
jQuery.fn.serializeAssoc = function() {
    var a = [];
    jQuery.each(this.serializeArray(), function(c, e) {
        var b = e.name.match(/(.*?)\[(.*?)\]/);
        if (b !== null) {
            var d = b[1];
            var f = b[2];
            if (!a[d]) {
                a[d] = []
            }
            if (a[d][f]) {
                if (jQuery.isArray(a[d][f])) {
                    a[d][f].push(e.value)
                } else {
                    a[d][f] = [];
                    a[d][f].push(e.value)
                }
            } else {
                a[d][f] = e.value
            }
        } else {
            if (a[e.name]) {
                if (jQuery.isArray(a[e.name])) {
                    a[e.name].push(e.value)
                } else {
                    a[e.name] = [];
                    a[e.name].push(e.value)
                }
            } else {
                a[e.name] = e.value
            }
        }
    });
    return a
};

function str_replace(c, d, b) {
    var a = c.split(d);
    return a.join(b)
}

function pps_str_replace(c, d, b) {
    var a = c.split(d);
    return a.join(b)
}

function nameToClassId(a) {
    return str_replace(str_replace(a, "]", ""), "[", "")
}

function strpos(b, c, d) {
    var a = b.indexOf(c, d);
    return a >= 0 ? a : false
}

function extend(b, a) {
    var c = function() {};
    c.prototype = a.prototype;
    b.prototype = new c();
    b.prototype.constructor = b;
    b.superclass = a.prototype
}

function toeRedirect(b, a) {
    if (a) {
        var c = window.open(b, "_blank");
        if (c) {
            c.focus()
        } else {
            document.location.href = b
        }
    } else {
        document.location.href = b
    }
}

function toeReload(a) {
    if (a) {
        toeRedirect(a)
    }
    document.location.reload()
}
jQuery.fn.toeRebuildSelect = function(b, c, d) {
    if (jQuery(this).tagName() == "SELECT" && typeof(b) == "object") {
        if (jQuery(b).length > 0) {
            if (typeof(d) == "undefined") {
                d = false
            }
            if (jQuery(this).children("option").length) {
                jQuery(this).children("option").remove()
            }
            if (typeof(c) == "undefined") {
                c = false
            }
            var a = "";
            for (var e in b) {
                a = "";
                if (d && ((c && e == d) || (b[e] == d))) {
                    a = "selected"
                }
                jQuery(this).append('<option value="' + (c ? e : b[e]) + '" ' + a + ">" + b[e] + "</option>")
            }
        }
    }
};

function toeInArray(c, b) {
    if (typeof(b) == "object") {
        for (var a in b) {
            if (b[a] == c) {
                return a
            }
        }
    } else {
        if (typeof(b) == "array") {
            return jQuery.inArray(c, b)
        }
    }
    return -1
}
jQuery.fn.setReadonly = function() {
    jQuery(this).addClass("toeReadonly").attr("readonly", "readonly")
};
jQuery.fn.unsetReadonly = function() {
    jQuery(this).removeClass("toeReadonly").removeAttr("readonly", "readonly")
};
jQuery.fn.getClassId = function(a, c) {
    var b = jQuery(this).attr("class");
    b = b.substr(strpos(b, a + "_"));
    if (strpos(b, " ")) {
        b = b.substr(0, strpos(b, " "))
    }
    b = b.split("_");
    b = b[1];
    return b
};

function toeTextIncDec(a, c) {
    var b = parseInt(jQuery("#" + a).val());
    if (isNaN(b)) {
        b = 0
    }
    if (!(c < 0 && b < 1)) {
        b += c
    }
    jQuery("#" + a).val(b)
}

function toeStrFirstUp(b) {
    b += "";
    var a = b.charAt(0).toUpperCase();
    return a + b.substr(1)
}

function toeListablePps(b) {
    this.params = jQuery.extend({}, b);
    this.table = jQuery(this.params.table);
    this.paging = jQuery(this.params.paging);
    this.perPage = this.params.perPage;
    this.list = this.params.list;
    this.count = this.params.count;
    this.page = this.params.page;
    this.pagingCallback = this.params.pagingCallback;
    var a = this;
    this.draw = function(k, j) {
        this.table.find("tr").not(".ppsExample, .ppsTblHeader").remove();
        var n = this.table.find(".ppsExample");
        for (var e in k) {
            var o = n.clone();
            for (var l in k[e]) {
                var f = o.find("." + l);
                if (f.length) {
                    var m = f.attr("valueTo");
                    if (m) {
                        var c = k[e][l];
                        var h = f.attr(m);
                        if (h) {
                            c = h + " " + c
                        }
                        f.attr(m, c)
                    } else {
                        f.html(k[e][l])
                    }
                }
            }
            o.removeClass("ppsExample").show();
            this.table.append(o)
        }
        if (this.paging) {
            this.paging.html("");
            if (j && j > k.length && this.perPage) {
                for (var e = 1; e <= Math.ceil(j / this.perPage); e++) {
                    var d = e - 1,
                        g = (d == this.page) ? jQuery("<b/>") : jQuery("<a/>");
                    if (d != this.page) {
                        g.attr("href", "#" + d).click(function() {
                            if (a.pagingCallback && typeof(a.pagingCallback) == "function") {
                                a.pagingCallback(parseInt(jQuery(this).attr("href").replace("#", "")));
                                return false
                            }
                        })
                    }
                    g.addClass("toePagingElement").html(e);
                    this.paging.append(g);
                    if (e % 20 == 0 && e) {
                        this.paging.append("<br />")
                    }
                }
            }
        }
    };
    if (this.list) {
        this.draw(this.list, this.count)
    }
}

function setCookiePps(a, e, b) {
    var f = new Date();
    f.setDate(f.getDate() + b);
    var c = "";
    if (typeof(e) == "array" || typeof(e) == "object") {
        c = "_JSON:" + JSON.stringify(e)
    } else {
        c = e
    }
    var d = escape(c) + ((b == null) ? "" : "; expires=" + f.toUTCString()) + "; path=/";
    document.cookie = a + "=" + d
}

function getCookiePps(a) {
    var c = document.cookie.split(a + "=");
    if (c.length == 2) {
        var b = unescape(c.pop().split(";").shift());
        if (b.indexOf("_JSON:") === 0) {
            b = JSON.parse(b.split("_JSON:").pop())
        }
        return b
    }
    return null
}

function delCookiePps(a) {
    document.cookie = a + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT;"
}

function callUserFuncArray(cb, parameters) {
    var func;
    if (typeof cb === "string") {
        func = (typeof this[cb] === "function") ? this[cb] : func = (new Function(null, "return " + cb))()
    } else {
        if (Object.prototype.toString.call(cb) === "[object Array]") {
            func = (typeof cb[0] == "string") ? eval(cb[0] + "['" + cb[1] + "']") : func = cb[0][cb[1]]
        } else {
            if (typeof cb === "function") {
                func = cb
            }
        }
    }
    if (typeof func !== "function") {
        throw new Error(func + " is not a valid function")
    }
    return (typeof cb[0] === "string") ? func.apply(eval(cb[0]), parameters) : (typeof cb[0] !== "object") ? func.apply(null, parameters) : func.apply(cb[0], parameters)
}
jQuery.fn.zoom = function(b, a) {
    a = a ? a : "center center";
    jQuery(this).data("zoom", b);
    return jQuery(this).css({
        // "-moz-transform": "scale(" + b + ")",
        "-moz-transform-origin": a,
        // "-o-transform": "scale(" + b + ")",
        "-o-transform-origin": a,
        // "-webkit-transform": "scale(" + b + ")",
        "-webkit-transform-origin": a,
        // transform: "scale(" + b + ")",
        "transform-origin": a
    })
};
jQuery.fn.ppsZoom = function(b, a) {
    a = a ? a : "center center";
    jQuery(this).data("zoom", b);
    return jQuery(this).css({
        // "-moz-transform": "scale(" + b + ")",
        "-moz-transform-origin": a,
        // "-o-transform": "scale(" + b + ")",
        "-o-transform-origin": a,
        // "-webkit-transform": "scale(" + b + ")",
        "-webkit-transform-origin": a,
        // transform: "scale(" + b + ")",
        "transform-origin": a
    })
};
jQuery.fn.scrollWidth = function() {
    var c = document.createElement("p");
    c.style.width = "100%";
    c.style.height = "200px";
    var d = document.createElement("div");
    d.style.position = "absolute";
    d.style.top = "0px";
    d.style.left = "0px";
    d.style.visibility = "hidden";
    d.style.width = "200px";
    d.style.height = "150px";
    d.style.overflow = "hidden";
    d.appendChild(c);
    document.body.appendChild(d);
    var b = c.offsetWidth;
    d.style.overflow = "scroll";
    var a = c.offsetWidth;
    if (b == a) {
        a = d.clientWidth
    }
    document.body.removeChild(d);
    return (b - a)
};

function toeGetImgAttachId(a) {
    var d = jQuery(a).attr("class"),
        c = 0;
    if (d && d != "") {
        var b = d.match(/wp-image-(\d+)/);
        if (b && b[1]) {
            c = parseInt(b[1])
        }
    }
    return c
}

function toeGetHashParams() {
    var a = window.location.hash.split("#"),
        c = [];
    for (var b in a) {
        if (a[b] && a[b] != "") {
            c.push(a[b])
        }
    }
    return c
}

function traverseElement(a, b, c, e) {
    if (!/^(script|style)$/.test(a.tagName)) {
        var d = a.lastChild;
        while (d) {
            if (d.nodeType == 1) {
                traverseElement(d, b, c, e)
            } else {
                if (d.nodeType == 3) {
                    c(d, b, e)
                }
            }
            d = d.previousSibling
        }
    }
}

function textReplacerFunc(b, a, c) {
    b.data = b.data.replace(a, c)
}

function replaceWords(c, d) {
    var a = document.createElement("div");
    a.innerHTML = c;
    for (var b in d) {
        traverseElement(a, new RegExp(b, "g"), textReplacerFunc, d[b])
    }
    return a.innerHTML
}

function toeSelectText(b) {
    var d = document,
        e = jQuery(b).get(0),
        a, c;
    if (d.body.createTextRange) {
        a = d.body.createTextRange();
        a.moveToElementText(e);
        a.select()
    } else {
        if (window.getSelection) {
            c = window.getSelection();
            a = d.createRange();
            a.selectNodeContents(e);
            c.removeAllRanges();
            c.addRange(a)
        }
    }
}
jQuery.fn.animationDuration = function(c, a) {
    if (a) {
        c = parseFloat(c) / 1000
    }
    var b = c + "s";
    return jQuery(this).css({
        "webkit-animation-duration": b,
        "-moz-animation-duration": b,
        "-o-animation-duration": b,
        "animation-duration": b
    })
};

function ppsStrToMs(e) {
    var f = e.split(" ");
    if (f.length == 2) {
        e = f[0] + " ";
        var d = f[1].split(":");
        for (var c = 0; c < 3; c++) {
            e += d[c] ? d[c] : "00";
            if (c < 2) {
                e += ":"
            }
        }
    }
    var a = new Date(str_replace(e, "-", "/")),
        b = 0;
    if (a) {
        b = a.getTime()
    }
    return b
}
Date.prototype.format = function(d) {
    var b = "";
    var f = Date.replaceChars;
    for (var c = 0; c < d.length; c++) {
        var a = d.charAt(c);
        if (c - 1 >= 0 && d.charAt(c - 1) == "\\") {
            b += a
        } else {
            if (f[a]) {
                b += f[a].call(this)
            } else {
                if (a != "\\") {
                    b += a
                }
            }
        }
    }
    return b
};
Date.replaceChars = {
    shortMonths: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    longMonths: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
    shortDays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    longDays: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    d: function() {
        return (this.getDate() < 10 ? "0" : "") + this.getDate()
    },
    D: function() {
        return Date.replaceChars.shortDays[this.getDay()]
    },
    j: function() {
        return this.getDate()
    },
    l: function() {
        return Date.replaceChars.longDays[this.getDay()]
    },
    N: function() {
        return this.getDay() + 1
    },
    S: function() {
        return this.getDate() % 10 == 1 && this.getDate() != 11 ? "st" : this.getDate() % 10 == 2 && this.getDate() != 12 ? "nd" : this.getDate() % 10 == 3 && this.getDate() != 13 ? "rd" : "th"
    },
    w: function() {
        return this.getDay()
    },
    z: function() {
        var a = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((this - a) / 86400000)
    },
    W: function() {
        var a = new Date(this.getFullYear(), 0, 1);
        return Math.ceil(((this - a) / 86400000 + a.getDay() + 1) / 7)
    },
    F: function() {
        return Date.replaceChars.longMonths[this.getMonth()]
    },
    m: function() {
        return (this.getMonth() < 9 ? "0" : "") + (this.getMonth() + 1)
    },
    M: function() {
        return Date.replaceChars.shortMonths[this.getMonth()]
    },
    n: function() {
        return this.getMonth() + 1
    },
    t: function() {
        var a = new Date;
        return (new Date(a.getFullYear(), a.getMonth(), 0)).getDate()
    },
    L: function() {
        var a = this.getFullYear();
        return a % 400 == 0 || a % 100 != 0 && a % 4 == 0
    },
    o: function() {
        var a = new Date(this.valueOf());
        a.setDate(a.getDate() - (this.getDay() + 6) % 7 + 3);
        return a.getFullYear()
    },
    Y: function() {
        return this.getFullYear()
    },
    y: function() {
        return ("" + this.getFullYear()).substr(2)
    },
    a: function() {
        return this.getHours() < 12 ? "am" : "pm"
    },
    A: function() {
        return this.getHours() < 12 ? "AM" : "PM"
    },
    B: function() {
        return Math.floor(((this.getUTCHours() + 1) % 24 + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24)
    },
    g: function() {
        return this.getHours() % 12 || 12
    },
    G: function() {
        return this.getHours()
    },
    h: function() {
        return ((this.getHours() % 12 || 12) < 10 ? "0" : "") + (this.getHours() % 12 || 12)
    },
    H: function() {
        return (this.getHours() < 10 ? "0" : "") + this.getHours()
    },
    i: function() {
        return (this.getMinutes() < 10 ? "0" : "") + this.getMinutes()
    },
    s: function() {
        return (this.getSeconds() < 10 ? "0" : "") + this.getSeconds()
    },
    u: function() {
        var a = this.getMilliseconds();
        return (a < 10 ? "00" : a < 100 ? "0" : "") + a
    },
    e: function() {
        return "Not Yet Supported"
    },
    I: function() {
        var c = null;
        for (var a = 0; a < 12; ++a) {
            var d = new Date(this.getFullYear(), a, 1);
            var b = d.getTimezoneOffset();
            if (c === null) {
                c = b
            } else {
                if (b < c) {
                    c = b;
                    break
                } else {
                    if (b > c) {
                        break
                    }
                }
            }
        }
        return this.getTimezoneOffset() == c | 0
    },
    O: function() {
        return (-this.getTimezoneOffset() < 0 ? "-" : "+") + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? "0" : "") + Math.abs(this.getTimezoneOffset() / 60) + "00"
    },
    P: function() {
        return (-this.getTimezoneOffset() < 0 ? "-" : "+") + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? "0" : "") + Math.abs(this.getTimezoneOffset() / 60) + ":00"
    },
    T: function() {
        var b = this.getMonth();
        this.setMonth(0);
        var a = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, "$1");
        this.setMonth(b);
        return a
    },
    Z: function() {
        return -this.getTimezoneOffset() * 60
    },
    c: function() {
        return this.format("Y-m-d\\TH:i:sP")
    },
    r: function() {
        return this.toString()
    },
    U: function() {
        return this.getTime() / 1000
    }
};

function isMobilePps() {
    var a = false;
    if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
        a = true
    }
    return a
}

function isNumericPps(a) {
    if (jQuery.isNumeric) {
        return jQuery.isNumeric(a)
    }
    return !isNaN(parseFloat(a)) && isFinite(a)
}

function disableScrollPps(b) {
    var a = jQuery(b);
    a.data("prev-overflow", a.css("overflow")).css("overflow", "hidden")
}

function enableScrollPps(b) {
    var a = jQuery(b);
    var c = a.data("prev-overflow");
    if (!c || c == "") {
        c = "visible"
    }
    a.css("overflow", c)
}

function randPps(b, a, d) {
    var c = (Math.random() * (a - b)) + b;
    return d ? c : Math.round(c)
};
