/* Twins Sushi — interactions du site (généré par maquettes/_outils/make_site_assets.py).
   Noyau des maquettes Indigo.
   Reprend les fonctions du site actuel (assets/app.js, assets/home.js) :
   carte, recherche, filtres Végé / Épicé, navigation par rubrique, panier, commande WhatsApp,
   « composez votre box », horaires du jour, thème clair / sombre, plan chargé au clic.
   Les données viennent de carte-vf.js (carte VF) ; rien n'est lu en ligne. */
(function () {
  "use strict";
  var CARTE = window.TWINS_CARTE || [];
  var W = window.TWINS || {};
  var cfg = { wa: W.wa || "212619162094", waShown: W.waShown || "06 19 16 20 94", rue: W.rue || "", ville: W.ville || "" };
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  function esc(s) { return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function norm(s) { return (s || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, ""); }

  var items = {};
  CARTE.forEach(function (r) { r.items.forEach(function (it) { it.cat = r.id; it.catTitle = r.title; it.catNum = r.num; items[it.code] = it; }); });
  function byName(n) { for (var k in items) if (items[k].name === n) return items[k]; return null; }

  var T = window.Twins = { cfg: cfg, carte: CARTE, items: items, byName: byName, esc: esc, $: $, $$: $$ };
  T.base = "";
  T.photo = function (it) { return it.photo || ""; };
  T.banner = function (r) { return r.banner ? T.base + "assets/bannieres/" + r.banner + ".webp" : ""; };
  T.minPrice = function (it) { return it.opts ? Math.min.apply(null, it.opts.map(function (o) { return o.p; })) : it.price; };
  T.priceText = function (it) { var p = T.minPrice(it); return (it.opts ? "dès " : "") + p; };
  T.stats = function () {
    var n = 0, min = 1e9;
    CARTE.forEach(function (r) { if (r.id === "boissons") return; r.items.forEach(function (it) { n++; min = Math.min(min, T.minPrice(it)); }); });
    return { plats: n, rubriques: CARTE.length, min: min };
  };
  T.badge = function (it) { return it.badge ? '<span class="badge ' + it.badge + '">' + (it.badge === "epice" ? "Épicé" : "Veggie") + "</span>" : ""; };
  T.pcs = function (it) { return it.pcs ? (it.pcs + (it.pcs > 1 ? " pièces" : " pièce")) : ""; };

  /* Fiche produit : même balisage pour les 3 versions (classes .p, .ph, .add attendues par le noyau). */
  T.photoBox = function (it) {
    return '<div class="ph">' + T.badge(it) + (it.photo
      ? '<img src="' + T.photo(it) + '" alt="' + esc(it.name) + '"' + (location.search.indexOf("eager") < 0 ? ' loading="lazy"' : "") + ' decoding="async">'
      : '<span class="nophoto" aria-label="Photo à venir">?</span>') + "</div>";
  };
  T.optSelect = function (it) {
    if (!it.opts) return "";
    return '<label class="opt"><span class="sr">Choix</span><select class="optsel" aria-label="Choix pour ' + esc(it.name) + '">' +
      it.opts.map(function (o, i) { return '<option value="' + i + '">' + esc(o.l) + " · " + o.p + "</option>"; }).join("") + "</select></label>";
  };
  T.addBtn = function (it) {
    return '<button class="add" type="button" aria-label="Ajouter ' + esc(it.name) + ' à ma commande">' +
      '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg></button>';
  };
  T.attrs = function (it) {
    return ' class="p" data-code="' + it.code + '" data-badge="' + it.badge + '" data-search="' + esc(it.name + " " + it.desc + " " + it.catTitle) + '"';
  };

  /* ---------- Thème clair / sombre ---------- */
  var root = document.documentElement;
  function setTheme(t) { root.setAttribute("data-theme", t); try { localStorage.setItem("twins-theme-v2", t); } catch (e) {} }
  try { var st = localStorage.getItem("twins-theme-v2"); if (st === "alt" || st === "main") root.setAttribute("data-theme", st); } catch (e) {}
  document.addEventListener("click", function (e) {
    var b = e.target.closest && e.target.closest(".theme"); if (!b) return;
    setTheme(root.getAttribute("data-theme") === "alt" ? "main" : "alt");
  });

  /* ---------- Horaires, plan ---------- */
  T._hoursRows = function () {
    return cfg.horaires.map(function (h) { return '<tr data-days="' + h.days + '"><td>' + esc(h.label) + "</td><td>" + esc(h.text) + "</td></tr>"; }).join("");
  };
  function initInfos() {
    var d = String(new Date().getDay());
    $$(".hours tr").forEach(function (tr) { if ((tr.getAttribute("data-days") || "").split(",").indexOf(d) > -1) tr.classList.add("today"); });
    $$(".map-load").forEach(function (b) {
      b.addEventListener("click", function () {
        var box = b.closest(".map"), f = document.createElement("iframe");
        f.src = box.getAttribute("data-src") || ("https://www.google.com/maps?q=" + encodeURIComponent(cfg.rue + " " + cfg.ville) + "&output=embed");
        f.title = "Plan d’accès Twins Sushi"; f.loading = "lazy"; f.referrerPolicy = "no-referrer-when-downgrade";
        box.innerHTML = ""; box.appendChild(f);
      });
    });
    $$("[data-wa]").forEach(function (a) { a.href = "https://wa.me/" + cfg.wa + "?text=" + encodeURIComponent("Bonjour Twins Sushi, je souhaite passer une commande."); a.target = "_blank"; a.rel = "noopener"; });
  }

  /* ---------- Navigation par rubrique + recherche + filtres ---------- */
  var io = null, active = {};
  function initNav() {
    var chips = $$(".cats a"), cats = $$(".cat");
    if (io) io.disconnect();
    if ("IntersectionObserver" in window && cats.length) {
      io = new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          if (!e.isIntersecting) return;
          chips.forEach(function (c) {
            var on = c.getAttribute("href") === "#" + e.target.id; c.classList.toggle("on", on);
            if (on) { var bar = c.parentNode; bar.scrollTo({ left: c.offsetLeft - bar.clientWidth / 2 + c.clientWidth / 2, behavior: "smooth" }); }
          });
        });
      }, { rootMargin: "-45% 0px -50% 0px" });
      cats.forEach(function (c) { io.observe(c); });
    }
  }
  function applyFilter() {
    var q = $("#q"), term = norm(q ? q.value.trim() : ""), keys = Object.keys(active).filter(function (k) { return active[k]; }), shown = 0;
    $$(".cat").forEach(function (cat) {
      var n = 0;
      $$(".p", cat).forEach(function (p) {
        var ok = (!term || norm(p.getAttribute("data-search")).indexOf(term) > -1) && (!keys.length || keys.indexOf(p.getAttribute("data-badge")) > -1);
        p.hidden = !ok; if (ok) n++;
      });
      cat.hidden = n === 0; shown += n;
    });
    var em = $(".empty"); if (em) em.hidden = shown > 0;
    var qc = $("#q-count"); if (qc) qc.textContent = (term || keys.length) ? shown + (shown > 1 ? " plats" : " plat") : "";
  }
  function initFilters() {
    var q = $("#q"); if (q) q.addEventListener("input", applyFilter);
    $$(".fchip").forEach(function (b) {
      b.addEventListener("click", function () {
        var k = b.getAttribute("data-f"), on = b.getAttribute("aria-pressed") !== "true";
        b.setAttribute("aria-pressed", on ? "true" : "false"); active[k] = on; applyFilter();
      });
    });
  }

  /* ---------- Panier et commande WhatsApp ---------- */
  var KEY = "twins-panier-v2", cart = {};
  try { cart = JSON.parse(localStorage.getItem(KEY) || "{}") || {}; } catch (e) { cart = {}; }
  function info(key) {
    var p = key.split("#"), it = items[p[0]]; if (!it) return null;
    var o = p[1] != null && it.opts ? it.opts[+p[1]] : null;
    return { name: it.name + (o ? " (" + o.l + ")" : ""), price: o ? o.p : it.price, img: T.photo(it) };
  }
  Object.keys(cart).forEach(function (k) { if (!info(k)) delete cart[k]; });
  function save() { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} }
  function count() { return Object.keys(cart).reduce(function (a, k) { return a + cart[k]; }, 0); }
  function total() { return Object.keys(cart).reduce(function (a, k) { return a + cart[k] * info(k).price; }, 0); }
  function fmt(n) { return n.toLocaleString("fr-FR") + " DH"; }
  var toastEl, toastT;
  function toast(msg) { if (!toastEl) return; toastEl.textContent = msg; toastEl.classList.add("on"); clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove("on"); }, 1800); }

  T.mountDrawer = function () {
    var d = document.createElement("div");
    d.innerHTML =
      '<div class="drawer" id="panier" hidden role="dialog" aria-modal="true" aria-labelledby="h-panier"><div class="panel">' +
      '<header><h2 id="h-panier">Ma commande</h2><button class="x" type="button" data-close aria-label="Fermer">✕</button></header>' +
      '<div class="body"><div id="lines"></div><form class="form" onsubmit="return false">' +
      '<fieldset class="mode"><legend>Comment ?</legend><label><input type="radio" name="c-mode" value="Livraison" checked><span>Livraison</span></label><label><input type="radio" name="c-mode" value="À emporter"><span>À emporter</span></label></fieldset>' +
      '<label for="c-adr" class="adr">Adresse ou quartier<input id="c-adr" type="text" autocomplete="street-address" placeholder="Ex. Maârif, rue Ibnou Faris…" maxlength="120"></label>' +
      '<label for="c-nom">Votre prénom<input id="c-nom" type="text" autocomplete="given-name" placeholder="Ex. Yasmine" maxlength="40"></label>' +
      '<label for="c-heure">Heure souhaitée<select id="c-heure"><option>Dès que possible</option></select></label>' +
      '<label for="c-note">Remarque (facultatif)<textarea id="c-note" placeholder="Sans wasabi, sauce en plus…" maxlength="300"></textarea></label></form></div>' +
      '<footer><div class="total"><span>Total</span><b id="total">0 DH</b></div>' +
      '<a class="btn btn-wa" id="send" href="#" target="_blank" rel="noopener" aria-disabled="true">Envoyer sur WhatsApp</a>' +
      '<p class="note">Votre commande s’ouvre dans WhatsApp, prête à envoyer. Nous confirmons l’heure, la livraison ou le retrait au ' + cfg.waShown + '.</p></footer></div></div>' +
      '<div class="fab"><button class="cart-btn" type="button" data-open-cart>Voir ma commande<span class="n cart-count">0</span></button></div>' +
      '<div class="toast" role="status" aria-live="polite"></div>';
    while (d.firstChild) document.body.appendChild(d.firstChild);
    toastEl = $(".toast");
    var sel = $("#c-heure"), now = new Date(), start = new Date(now.getTime() + 30 * 60000), opts = ["<option>Dès que possible</option>"];
    start.setMinutes(Math.ceil(start.getMinutes() / 15) * 15, 0, 0);
    for (var i = 0; i < 12; i++) { var s = new Date(start.getTime() + i * 15 * 60000); opts.push("<option>Vers " + String(s.getHours()).padStart(2, "0") + "h" + String(s.getMinutes()).padStart(2, "0") + "</option>"); }
    sel.innerHTML = opts.join("");
    ["#c-nom", "#c-heure", "#c-note", "#c-adr"].forEach(function (s2) { var el = $(s2); el.addEventListener("input", updateLink); el.addEventListener("change", updateLink); });
    $$('input[name="c-mode"]').forEach(function (r) { r.addEventListener("change", function () { var a = $(".adr"); if (a) a.hidden = mode() !== "Livraison"; updateLink(); }); });
    try { var last = JSON.parse(localStorage.getItem(KEY + "-client") || "{}"); if (last.nom) $("#c-nom").value = last.nom; if (last.adr) $("#c-adr").value = last.adr; } catch (e) {}
    $("#send").addEventListener("click", function (e) {
      if (this.getAttribute("aria-disabled") === "true") { e.preventDefault(); $("#c-nom").focus(); return; }
      try { localStorage.setItem(KEY + "-client", JSON.stringify({ nom: $("#c-nom").value.trim(), adr: $("#c-adr").value.trim() })); } catch (err) {}
    });
    render();
  };
  function render() {
    var n = count();
    $$(".cart-count").forEach(function (el) { el.textContent = n; });
    var fab = $(".fab"); if (fab) fab.classList.toggle("show", n > 0);
    $$(".p .add").forEach(function (b) {
      var c = b.closest(".p").getAttribute("data-code");
      b.classList.toggle("in", Object.keys(cart).some(function (k) { return k.split("#")[0] === c; }));
    });
    var list = $("#lines"); if (!list) return;
    var keys = Object.keys(cart);
    list.innerHTML = !keys.length
      ? '<div class="cart-empty"><p>Votre commande est vide.</p><a class="btn btn-ligne" href="#carte" data-close>Voir la carte</a></div>'
      : keys.map(function (k) {
          var it = info(k);
          return '<div class="line">' + (it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy">' : '<span class="noimg">?</span>') +
            '<div><div class="nm">' + esc(it.name) + '</div><div class="pr">' + fmt(it.price * cart[k]) + '</div></div><div class="qty">' +
            '<button type="button" data-dec="' + esc(k) + '" aria-label="Retirer un ' + esc(it.name) + '">−</button><span>' + cart[k] +
            '</span><button type="button" data-inc="' + esc(k) + '" aria-label="Ajouter un ' + esc(it.name) + '">+</button></div></div>';
        }).join("");
    $("#total").textContent = fmt(total());
    $$("#panier form, #panier footer").forEach(function (el) { el.hidden = !keys.length; });
    updateLink();
  }
  function message() {
    var l = ["Bonjour Twins Sushi, je souhaite passer une commande :", ""];
    Object.keys(cart).forEach(function (k) { var it = info(k); l.push("• " + cart[k] + " × " + it.name + " — " + fmt(it.price * cart[k])); });
    l.push("", "Total : " + fmt(total()));
    var nm = $("#c-nom"), hr = $("#c-heure"), nt = $("#c-note"), ad = $("#c-adr"), md = mode();
    if (md) l.push("Mode : " + md);
    if (md === "Livraison" && ad && ad.value.trim()) l.push("Adresse : " + ad.value.trim());
    if (nm && nm.value.trim()) l.push("Nom : " + nm.value.trim());
    if (hr) l.push("Heure souhaitée : " + hr.value);
    if (nt && nt.value.trim()) l.push("Remarque : " + nt.value.trim());
    return l.join("\n");
  }
  function mode() { var r = document.querySelector('input[name="c-mode"]:checked'); return r ? r.value : ""; }
  function updateLink() {
    var a = $("#send"); if (!a) return; var nm = $("#c-nom");
    a.setAttribute("aria-disabled", count() > 0 && nm && nm.value.trim().length > 1 ? "false" : "true");
    a.href = "https://wa.me/" + cfg.wa + "?text=" + encodeURIComponent(message());
  }
  function openCart() { var d = $("#panier"); if (d) { d.hidden = false; document.body.style.overflow = "hidden"; var x = $(".x", d); if (x) x.focus(); } }
  function closeCart() { var d = $("#panier"); if (d) { d.hidden = true; document.body.style.overflow = ""; } }
  T.addToCart = function (key, label) { cart[key] = (cart[key] || 0) + 1; save(); render(); toast("Ajouté : " + (label || info(key).name)); };
  function closeQV() { var w = $(".qvw"); if (w) { w.parentNode.removeChild(w); document.body.style.overflow = ""; } }
  T.openQV = function (code) {
    var it = items[code]; if (!it) return; closeQV();
    var w = document.createElement("div"); w.className = "qvw"; w.setAttribute("role", "dialog"); w.setAttribute("aria-modal", "true"); w.setAttribute("aria-label", it.name);
    var meta = [T.pcs(it), it.pers].filter(Boolean).join(" · ");
    w.innerHTML = '<div class="qv-panel p qv" data-code="' + it.code + '" data-badge="' + it.badge + '"><button class="x" type="button" data-qvclose aria-label="Fermer">✕</button>' +
      (it.photo ? '<div class="qv-ph">' + T.badge(it) + '<img src="' + esc(T.photo(it)) + '" alt="' + esc(it.name) + '"></div>' : "") +
      '<div class="qv-txt"><span class="kicker">' + esc(it.catTitle) + '</span><h3>' + esc(it.name) + "</h3>" + (meta ? '<p class="pcs">' + esc(meta) + "</p>" : "") +
      (it.desc ? '<p class="d">' + esc(it.desc) + "</p>" : "") + T.optSelect(it) +
      '<div class="foot"><span class="price">' + T.priceText(it) + "</span>" + T.addBtn(it) + "</div></div></div>";
    document.body.appendChild(w); document.body.style.overflow = "hidden"; var x = $(".x", w); if (x) x.focus();
  };
  document.addEventListener("click", function (e) {
    var t = e.target, add = t.closest && t.closest(".add");
    if (t.closest && (t.closest("[data-qvclose]") || (t.classList && t.classList.contains("qvw")))) { closeQV(); return; }
    var qv = t.closest && t.closest(".p:not(.qv) .ph, .p:not(.qv) h4");
    if (qv && !add) { T.openQV(qv.closest(".p").getAttribute("data-code")); return; }
    if (add && !add.disabled && add.closest(".p")) {
      var p = add.closest(".p"), code = p.getAttribute("data-code"), it = items[code], sel = $(".optsel", p);
      T.addToCart(sel ? code + "#" + sel.value : code); if (p.classList.contains("qv")) closeQV(); return;
    }
    var inc = t.closest && t.closest("[data-inc]"), dec = t.closest && t.closest("[data-dec]");
    if (inc) { cart[inc.getAttribute("data-inc")]++; save(); render(); return; }
    if (dec) { var k = dec.getAttribute("data-dec"); cart[k]--; if (cart[k] <= 0) delete cart[k]; save(); render(); return; }
    if (t.closest && t.closest("[data-open-cart]")) { e.preventDefault(); openCart(); return; }
    if (t.closest && t.closest("[data-close]")) { closeCart(); return; }
    if (t === $("#panier")) closeCart();
  });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") { closeQV(); closeCart(); } });

  /* ---------- Composez votre box : 4 rolls, prix = somme des rolls ----------
     Sélection de rolls proposée dans la maquette : les California de la carte VF qui ont une photo. À valider. */
  T.initBox = function () {
    var box = $(".boxc"); if (!box) return;
    var MAX = 4, sel = [], picks = $$(".pick", box), slots = $$(".slot", box), tot = $("#box-total"), btn = $("#box-add"), cnt = $("#box-count"), msg = $("#box-msg");
    function say(t) { msg.textContent = t; msg.classList.add("on"); clearTimeout(say.t); say.t = setTimeout(function () { msg.classList.remove("on"); }, 2200); }
    var tabs = $$(".bx-tab", box);
    function showTab(id) {
      tabs.forEach(function (b) { b.setAttribute("aria-selected", b.getAttribute("data-bx") === id ? "true" : "false"); });
      picks.forEach(function (p) { p.hidden = !!id && p.getAttribute("data-cat") !== id; });
    }
    tabs.forEach(function (b) { b.addEventListener("click", function () { showTab(b.getAttribute("data-bx")); }); });
    if (tabs.length) showTab(tabs[0].getAttribute("data-bx"));
    function draw() {
      slots.forEach(function (s, i) {
        var c = sel[i]; s.classList.toggle("full", !!c);
        s.innerHTML = c ? '<img src="' + T.photo(c) + '" alt="' + esc(c.name) + '"><b class="rm" aria-hidden="true">✕</b>' : "<em>" + (i + 1) + "</em>";
        s.setAttribute("aria-label", c ? "Retirer " + c.name : "Emplacement " + (i + 1) + " vide"); s.setAttribute("tabindex", c ? "0" : "-1");
      });
      tot.textContent = fmt(sel.reduce(function (a, c) { return a + c.price; }, 0)); cnt.textContent = sel.length + "/" + MAX;
      picks.forEach(function (p) {
        var k = sel.filter(function (c) { return c.code === p.getAttribute("data-code"); }).length;
        p.classList.toggle("on", k > 0); p.querySelector(".q").textContent = "×" + k; p.classList.toggle("dim", sel.length >= MAX && !k);
      });
      btn.setAttribute("aria-disabled", sel.length ? "false" : "true");
    }
    function rmAt(i) { if (i > -1 && i < sel.length) { var c = sel.splice(i, 1)[0]; draw(); say(c.name + " retiré"); } }
    picks.forEach(function (p) {
      p.addEventListener("click", function (e) {
        var code = p.getAttribute("data-code");
        if (e.target.closest(".minus")) { e.stopPropagation(); return rmAt(sel.map(function (c) { return c.code; }).lastIndexOf(code)); }
        if (sel.length >= MAX) { say("Box pleine : retirez un roll pour en ajouter un autre."); return; }
        sel.push(items[code]); draw();
      });
    });
    slots.forEach(function (s, i) {
      s.addEventListener("click", function () { if (sel[i]) rmAt(i); });
      s.addEventListener("keydown", function (e) { if ((e.key === "Enter" || e.key === " ") && sel[i]) { e.preventDefault(); rmAt(i); } });
    });
    $("#box-clear").addEventListener("click", function () { sel = []; draw(); });
    btn.addEventListener("click", function (e) {
      e.preventDefault(); if (!sel.length) return;
      sel.forEach(function (c) { cart[c.code] = (cart[c.code] || 0) + 1; }); save(); render(); sel = []; draw(); openCart();
    });
    draw();
  };

  /* ---------- Ouvert maintenant ? ---------- */
  function hm(x) { var q = String(x || "0:0").split(":"); return (+q[0]) * 60 + (+q[1] || 0); }
  function fmtH(x) { var m = hm(x); if (m === 0 || m >= 1439) return "minuit"; var h = Math.floor(m / 60), mn = m % 60; return h + "h" + (mn ? String(mn).padStart(2, "0") : ""); }
  /* Gère des jours aux horaires différents (ex. vendredi 18h – 1h) et la fermeture après minuit :
     une ligne dont la fermeture est avant l'ouverture continue sur la nuit suivante. */
  T.openStatus = function (now) {
    var rows = (window.TWINS && window.TWINS.hours) || []; if (!rows.length || !window.Intl) return null;
    var o = {}; new Intl.DateTimeFormat("en-US", { timeZone: "Africa/Casablanca", weekday: "short", hour: "2-digit", minute: "2-digit", hourCycle: "h23" })
      .formatToParts(now || new Date()).forEach(function (q) { o[q.type] = q.value; });
    var d = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 }[o.weekday], m = (+o.hour % 24) * 60 + (+o.minute);
    var DN = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
    function on(r, day) { return String(r.days).split(",").indexOf(String(day)) > -1; }
    for (var i = 0; i < rows.length; i++) {
      var r = rows[i], a = hm(r.open), c = hm(r.close); if (c >= 1439) c = 1440; var night = c <= a;
      if (on(r, d) && m >= a && (night || m < c)) return { open: true, text: "Ouvert · jusqu’à " + fmtH(r.close) };
      if (night && on(r, (d + 6) % 7) && m < c) return { open: true, text: "Ouvert · jusqu’à " + fmtH(r.close) };
    }
    for (var k = 0; k < 8; k++) {
      var day = (d + k) % 7, best = null;
      rows.forEach(function (r) { if (on(r, day) && (k > 0 || hm(r.open) > m) && (best === null || hm(r.open) < hm(best))) best = r.open; });
      if (best !== null) return { open: false, text: "Fermé · ouvre " + (k === 0 ? "à " : k === 1 ? "demain à " : DN[day] + " à ") + fmtH(best) };
    }
    return null;
  };
  function initOpen() {
    var st = T.openStatus(); if (!st) return;
    $$(".openst").forEach(function (el) { el.textContent = st.text; el.classList.toggle("is-open", st.open); el.hidden = false; });
  }

  /* ---------- Démarrage : à appeler une fois la carte rendue par la version ---------- */
  T.start = function () { T.mountDrawer(); initInfos(); initNav(); initFilters(); T.initBox(); initOpen(); setInterval(initOpen, 60000); };
  T.start();
})();

/* « Beau gosse » (24 novembre 2026) : plateaux qui flottent au défilement, chiffres qui comptent,
   apparition douce, bouton « Ajouter » des signatures (ouvre l'aperçu du plat). */
(function () {
  "use strict";
  var T = window.Twins, reduce = window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches;
  document.addEventListener("click", function (e) {
    var b = e.target.closest && e.target.closest("[data-qv]");
    if (b && T && T.openQV) { e.preventDefault(); T.openQV(b.getAttribute("data-qv")); }
  });
  if (reduce) return;
  var par = [].slice.call(document.querySelectorAll(".par")), ticking = false;
  function tick() {
    ticking = false; var vh = window.innerHeight;
    par.forEach(function (el) {
      var r = el.getBoundingClientRect(); if (r.bottom < -200 || r.top > vh + 200) return;
      var c = (r.top + r.height / 2 - vh / 2) * (+el.getAttribute("data-speed") || 0);
      el.style.transform = "translate3d(0," + (-c).toFixed(1) + "px,0)";
    });
  }
  window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(tick); } }, { passive: true });
  tick();
  if (!("IntersectionObserver" in window)) { [].forEach.call(document.querySelectorAll(".rv"), function (el) { el.classList.add("in"); }); return; }
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) {
      if (!e.isIntersecting) return; var el = e.target; io.unobserve(el);
      if (el.hasAttribute("data-to")) {
        var to = +el.getAttribute("data-to"), t0 = null, small = el.querySelector("small"), suf = small ? small.outerHTML : "";
        (function step(t) { if (!t0) t0 = t; var k = Math.min(1, (t - t0) / 1100); el.innerHTML = Math.round(to * (1 - Math.pow(1 - k, 3))) + suf; if (k < 1) requestAnimationFrame(step); })(performance.now());
      } else el.classList.add("in");
    });
  }, { rootMargin: "0px 0px -8% 0px" });
  [].forEach.call(document.querySelectorAll(".rv, .stats [data-to]"), function (el) { io.observe(el); });
})();
