.panel h2{margin:0 0 8px 0;font-size:1.1rem;}
.muted{opacity:.8}
.btn{border:0;padding:10px 14px;border-radius:10px;cursor:pointer;font-weight:700;}
.btn-primary{background:#111827;color:#fff;margin: 10px;}
.btn-secondary{background:#e5e7eb;color:#111827;}
.btn[disabled]{opacity:.5;cursor:not-allowed;}
.pill{display:inline-block;padding:4px 10px;background:#f3f4f6;font-size:.9rem;margin-left:8px;}
ul.route-list{list-style:none;padding:0;margin:10px 0 0 0;}
ul.route-list li{border-top:1px solid rgba(0,0,0,.06);padding:10px 0;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;}
ul.route-list a{text-decoration:none;font-weight:700;}
#map{height:520px;overflow:hidden;}
#elevation{height:260px;}

/* Modal permessi */
#permModal{
  display:none; position:fixed; inset:0;
  background:rgba(0,0,0,.55); z-index:999999;
  padding:16px;
}
#permModal .modal-card{
  max-width:560px; margin:10vh auto; background:#fff;
  border-radius:14px; padding:16px;
  box-shadow: 0 10px 30px rgba(0,0,0,.2);
}
#permModal .box{
  border:1px solid rgba(0,0,0,.08);
  border-radius:12px; padding:12px; margin:12px 0;
}
#permModal h3{margin:0 0 8px 0;}
/* Bootstrap .tooltip (opacity:0) rompe il tooltip SVG di leaflet-elevation */
#elevation .tooltip{
  opacity: 1 !important;
  filter: none !important;
}

/* evita che Bootstrap imposti display/posizionamenti strani */
#elevation .tooltip{
  position: static !important;
  z-index: auto !important;
}

