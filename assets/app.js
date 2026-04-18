/* Entegratör Rehberi — app.js v1 */
(function(){
'use strict';

// ── Compare bar (localStorage bazlı) ─────────────────
var CMP_KEY = 'ent_compare';
var CMP_MAX = 3;

function cmpGet(){ try { return JSON.parse(localStorage.getItem(CMP_KEY)||'[]'); } catch(e){ return []; } }
function cmpSet(arr){ localStorage.setItem(CMP_KEY, JSON.stringify(arr)); renderBar(); }
function cmpAdd(id, name){
  var arr = cmpGet();
  if (arr.find(function(x){return x.id==id;})) return;
  if (arr.length >= CMP_MAX){ alert('En fazla '+CMP_MAX+' entegratör karşılaştırabilirsiniz.'); return; }
  arr.push({id:id, name:name});
  cmpSet(arr);
}
function cmpRemove(id){
  var arr = cmpGet().filter(function(x){return x.id!=id;});
  cmpSet(arr);
  // Butonun görünümünü güncelle
  var btn = document.querySelector('[data-cmp-id="'+id+'"]');
  if (btn) btn.classList.remove('active');
}
function cmpClear(){ cmpSet([]); document.querySelectorAll('.btn-compare.active').forEach(function(b){b.classList.remove('active');}); }

function renderBar(){
  var bar = document.getElementById('cmp-bar');
  if (!bar) return;
  var arr = cmpGet();
  if (!arr.length){ bar.classList.remove('show'); return; }
  bar.classList.add('show');
  var items = arr.map(function(x){
    return '<span class="compare-chip">'+escapeHtml(x.name)+' <span class="x" data-cmp-remove="'+x.id+'">×</span></span>';
  }).join('');
  var ids = arr.map(function(x){return x.id;}).join(',');
  bar.innerHTML = '<div class="compare-count">'+arr.length+'</div>'+
    '<div class="compare-items">'+items+'</div>'+
    '<div class="compare-actions">'+
      '<a href="/karsilastir.php?ids='+ids+'" class="compare-go"><i class="fas fa-table-columns"></i> Karşılaştır</a>'+
      '<button class="compare-clear" onclick="window._cmpClear()">Temizle</button>'+
    '</div>';
}

window._cmpClear = cmpClear;

// ── Event delegation ─────────────────────────────────
document.addEventListener('click', function(e){
  var tgt = e.target.closest('[data-cmp-toggle]');
  if (tgt){
    e.preventDefault();
    var id = tgt.dataset.cmpToggle;
    var name = tgt.dataset.cmpName || 'Entegratör';
    var exists = cmpGet().find(function(x){return x.id==id;});
    if (exists){
      cmpRemove(id);
      tgt.classList.remove('active');
    } else {
      cmpAdd(id, name);
      tgt.classList.add('active');
    }
    return;
  }
  var rm = e.target.closest('[data-cmp-remove]');
  if (rm){ cmpRemove(rm.dataset.cmpRemove); return; }
  var nt = e.target.closest('.nav-mob');
  if (nt){ document.querySelector('.nav-links').classList.toggle('open'); return; }
  var ft = e.target.closest('.filter-toggle-mob');
  if (ft){ document.querySelector('.sidebar').classList.toggle('closed'); return; }
});

// ── Initial render ──────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  renderBar();
  // Mevcut sayfadaki compare butonlarının state'ini ayarla
  cmpGet().forEach(function(x){
    var btn = document.querySelector('[data-cmp-toggle="'+x.id+'"]');
    if (btn) btn.classList.add('active');
  });
});

// ── Helper ───────────────────────────────────────────
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];}); }

// ── Live search (index sayfasında) ──────────────────
var searchInp = document.getElementById('hero-search-inp');
var searchDD  = document.getElementById('hero-search-dd');
var searchTimer = null;

if (searchInp){
  searchInp.addEventListener('input', function(){
    clearTimeout(searchTimer);
    var q = this.value.trim();
    if (q.length < 2){ if(searchDD) searchDD.style.display='none'; return; }
    searchTimer = setTimeout(function(){
      fetch('/api/arama.php?q='+encodeURIComponent(q))
        .then(function(r){return r.json();})
        .then(function(d){
          if (!searchDD) return;
          if (!d.results || !d.results.length){
            searchDD.innerHTML = '<div class="sr-empty">Sonuç bulunamadı</div>';
          } else {
            searchDD.innerHTML = d.results.map(function(r){
              return '<a class="sr-item" href="/e.php?s='+encodeURIComponent(r.slug)+'">'+
                '<span class="sr-logo">'+(r.logo_url ? '<img src="'+r.logo_url+'" alt="">' : escapeHtml(r.firma_adi.charAt(0)))+'</span>'+
                '<span class="sr-info"><strong>'+escapeHtml(r.firma_adi)+'</strong><small>'+escapeHtml(r.kisa_aciklama||'')+'</small></span>'+
              '</a>';
            }).join('');
          }
          searchDD.style.display='block';
        });
    }, 250);
  });
  searchInp.addEventListener('blur', function(){ setTimeout(function(){ if(searchDD) searchDD.style.display='none'; }, 180); });
}

// ── Filter form (checkbox değişince GET submit) ─────
document.querySelectorAll('.filter-row input').forEach(function(cb){
  cb.addEventListener('change', function(){
    var form = this.closest('form');
    if (form) form.submit();
  });
});

})();
