// Small UI-only behaviors for demo
document.addEventListener('DOMContentLoaded', function(){
  // Login form — redirect to dashboard (demo)
  const loginForm = document.getElementById('loginForm');
  if(loginForm){
    loginForm.addEventListener('submit', function(e){
      // If the form is wired to server-side auth (auth.php), allow normal submit
      const action = (loginForm.getAttribute('action') || '').toLowerCase();
      if(action && action.indexOf('auth.php') !== -1){
        // do not intercept; let the browser POST to the server
        return;
      }

      // fallback demo behavior for static pages without server auth
      e.preventDefault();
      const form = new FormData(loginForm);
      const role = form.get('role') || 'dosen';
      const username = form.get('nidn');
      const password = form.get('password');

      if(role === 'admin'){
        // simple demo auth: username: admin, password: admin
        if(username === 'admin' && password === 'admin'){
          window.location.href = 'admin.html';
        } else {
          alert('Kredensial admin salah. Gunakan username: admin, password: admin (demo)');
        }
        return;
      }

      // default: dosen
      window.location.href = 'dashboard.html';
    });
  }

  // Dashboard: highlight clicked class card (active state)
  // Dashboard: highlight clicked class card (active state) + keyboard and persistence
  document.querySelectorAll('.class-card').forEach(card =>{
    // ensure focusability
    if(!card.hasAttribute('tabindex')) card.setAttribute('tabindex','0');

    // derive aria-label if missing
    if(!card.hasAttribute('aria-label')){
      const name = card.querySelector('.class-name')?.textContent?.trim();
      const course = card.querySelector('.course')?.textContent?.trim();
      const sched = card.querySelector('.class-schedule')?.textContent?.trim();
      if(name) card.setAttribute('aria-label', `${name} — ${course} — ${sched}`);
    }

    card.addEventListener('click', function(e){
      document.querySelectorAll('.class-card').forEach(c=>c.classList.remove('active'));
      card.classList.add('active');
      const id = card.getAttribute('data-class') || card.querySelector('.class-name')?.textContent;
      if(id) localStorage.setItem('aau-selected-class', id);
      if(card.getAttribute('href') === '#'){
        e.preventDefault();
      }
    });

    card.addEventListener('keydown', function(e){
      if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        card.click();
      }
    });
  });

  // restore active class from localStorage
  (function restoreActive(){
    const saved = localStorage.getItem('aau-selected-class');
    if(saved){
      const el = document.querySelector(`.class-card[data-class='${saved}']`);
      if(el) el.classList.add('active');
    }
  })();

  // Modal for quick-class detail
  const classModal = document.getElementById('classModal');
  const classModalCourse = document.getElementById('modalCourse');
  const classModalSchedule = document.getElementById('modalSchedule');
  const classModalOpenPage = document.getElementById('modalOpenPage');
  function openClassModal(data){
    if(!classModal) return;
    classModal.setAttribute('aria-hidden','false');
    classModalCourse.textContent = data.course || '—';
    classModalSchedule.textContent = data.schedule || '—';
    classModalOpenPage.setAttribute('href', data.href || '#');
    // focus
    classModalOpenPage.focus();
  }
  function closeClassModal(){
    if(!classModal) return;
    classModal.setAttribute('aria-hidden','true');
  }
  document.querySelectorAll('.class-card').forEach(card=>{
    card.addEventListener('click', function(e){
      const href = card.getAttribute('href') || '#';
      const data = {
        name: card.querySelector('.class-name')?.textContent?.trim(),
        course: card.querySelector('.course')?.textContent?.trim(),
        schedule: card.querySelector('.class-schedule')?.textContent?.trim(),
        href: card.getAttribute('data-href') || href,
        classId: card.getAttribute('data-class')
      };
      // if link points to a class page (static or server), follow; otherwise open modal and allow "open page" to include query param
      if(data.href && (data.href.indexOf('class.html') !== -1 || data.href.indexOf('class.php') !== -1)){
        // ensure query param contains class id
        const target = data.href.indexOf('?') === -1 ? `${data.href}?class=${data.classId}` : data.href;
        window.location.href = target;
        return;
      }

      // open modal for preview
      e.preventDefault();
      openClassModal(data);
      // set active state
      document.querySelectorAll('.class-card').forEach(c=>c.classList.remove('active'));
      card.classList.add('active');
      localStorage.setItem('aau-selected-class', card.getAttribute('data-class') || data.name);
    });
  });
  // class modal controls
  document.querySelectorAll('#classModal [data-close="true"]').forEach(btn => btn.addEventListener('click', closeClassModal));
  document.querySelector('#classModal .modal-close')?.addEventListener('click', closeClassModal);
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeClassModal();
  });
  document.querySelector('#classModal .modal-overlay')?.addEventListener('click', closeClassModal);

  // Class page: presensi time check and simple save behavior
  const presensiStatus = document.getElementById('presensiStatus');
  const saveBtn = document.getElementById('saveBtn');
  const presensiForm = document.getElementById('presensiForm');
  const rekapBody = document.getElementById('rekapBody');
  const totalCountEl = document.getElementById('totalCount');

  function formatTime(date){
    return String(date.getHours()).padStart(2,'0')+ ':' + String(date.getMinutes()).padStart(2,'0');
  }

  // persistent class identifier (from querystring if present)
  function getQueryParam(name){
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }
  const currentClass = getQueryParam('class') || document.body.dataset.classId || 'default';
  const rekapKey = `aau-rekap-${currentClass}`;

  // Data mapping: lecturers and their classes (can be extended server-side later)
  const LECTURERS = [
    {id: 'mnaf', name: 'Dr. Muhammad Nur Alfisyahr', classes: ['sersan-b','letnan-a']},
    {id: 'rini', name: 'Dr. Rini Suryanti', classes: ['kapten-c']}
  ];

  // Helper: find lecturer by id
  function getLecturer(id){
    return LECTURERS.find(l=>l.id === id) || null;
  }

  // scheduled window check (uses simulation when enabled)
  function isWithinPresensi(){
    const simulateToggle = document.getElementById('simulateToggle');
    const simulateTime = document.getElementById('simulateTime');
    let now = new Date();

    if(simulateToggle && simulateToggle.checked && simulateTime && simulateTime.value){
      const parts = simulateTime.value.split(':');
      now = new Date(); now.setHours(parseInt(parts[0],10), parseInt(parts[1],10), 0, 0);
    }

    // Extract schedule from page
    let scheduleText = '';
    const scheduleEl = document.querySelector('.info-grid div:nth-child(2) div, [class*="schedule"]');
    if(scheduleEl) scheduleText = scheduleEl.textContent?.trim() || '';
    
    // If no schedule found, fallback to default 08:00-09:30
    if(!scheduleText){
      const start = new Date(now); start.setHours(8,0,0,0);
      const end   = new Date(now); end.setHours(9,30,0,0);
      return now >= start && now <= end;
    }

    // Parse schedule: expected format "Hari, HH:MM - HH:MM"
    // Examples: "Senin, 08:00 - 09:30" or "Jumat, 12:00 - 15:30"
    const dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const currentDay = dayNames[now.getDay()];
    const currentHour = now.getHours();
    const currentMin = now.getMinutes();
    
    // Check if today matches schedule day
    if(!scheduleText.toLowerCase().includes(currentDay.toLowerCase())){
      return false;
    }

    // Extract time range from schedule
    const timeMatch = scheduleText.match(/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/);
    if(!timeMatch){
      // Invalid format, use default
      const start = new Date(now); start.setHours(8,0,0,0);
      const end   = new Date(now); end.setHours(9,30,0,0);
      return now >= start && now <= end;
    }

    const startHour = parseInt(timeMatch[1], 10);
    const startMin = parseInt(timeMatch[2], 10);
    const endHour = parseInt(timeMatch[3], 10);
    const endMin = parseInt(timeMatch[4], 10);

    // Convert to minutes for easier comparison
    const currentTime = currentHour * 60 + currentMin;
    const startTime = startHour * 60 + startMin;
    const endTime = endHour * 60 + endMin;

    return currentTime >= startTime && currentTime <= endTime;
  }

  // Persisted rekap data helper
  function loadRekap(){
    const stored = localStorage.getItem(rekapKey);
    let rows = [];
    if(stored){
      try{ rows = JSON.parse(stored); }catch(e){ rows = []; }
    } else {
      // if no stored data, seed from existing table rows
      document.querySelectorAll('#rekapBody tr').forEach(tr =>{
        const tds = tr.querySelectorAll('td');
        if(tds.length >= 3) rows.push({date: tds[0].textContent.trim(), time: tds[1].textContent.trim(), status: tds[2].textContent.trim()});
      });
      localStorage.setItem(rekapKey, JSON.stringify(rows));
    }
    // render
    rekapBody.innerHTML = '';
    rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.date}</td><td>${r.time}</td><td>${r.status}</td>`;
      rekapBody.appendChild(tr);
    });
    updateTotal(rows);
    return rows;
  }

  function saveRekap(rows){
    localStorage.setItem(rekapKey, JSON.stringify(rows));
  }

  function updateTotal(rows){
    const total = rows.filter(r=>r.status === 'Hadir').length;
    totalCountEl.textContent = total;
  }

  // initial load
  let rekapRows = [];

  // If on a server-backed class page, prefer loading server-side presensi
  const classId = window.AAU_CLASS_ID || document.body.dataset.classId;
  async function loadServerRekapIfAny(){
    if(!classId || !rekapBody) return false;
    try{
      const res = await fetch(`api/get_presensi.php?class=${classId}`);
      const json = await res.json();
      if(json && json.ok){
        rekapRows = json.data.map(r=>({date: (r.date || '-'), time: (r.time || '-'), status: r.status}));
        rekapBody.innerHTML = '';
        rekapRows.forEach(r=>{ const tr = document.createElement('tr'); tr.innerHTML = `<td>${r.date}</td><td>${r.time}</td><td>${r.status}</td>`; rekapBody.appendChild(tr); });
        updateTotal(rekapRows);
        return true;
      }
    }catch(e){ }
    return false;
  }

  (async function(){ if(!(await loadServerRekapIfAny())){ if(rekapBody) rekapRows = loadRekap(); }})();

  // Admin pages: render lecturers list and classes via server
  const lecturersGrid = document.getElementById('lecturersGrid');
  const searchLecturer = document.getElementById('searchLecturer');
  const refreshList = document.getElementById('refreshList');
  const classesGrid = document.getElementById('classesGrid');
  const modal = document.getElementById('modalOverlay');
  const modalContent = document.getElementById('modalContent');

  function showModal(html){ if(!modal) return; modalContent.innerHTML = html; modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ if(!modal) return; modal.setAttribute('aria-hidden','true'); modalContent.innerHTML = ''; }
  document.querySelector('.modal-close')?.addEventListener('click', closeModal);
  document.querySelectorAll('[data-close="true"]').forEach(btn => btn.addEventListener('click', closeModal));

  function toast(msg, type){ const wrap = document.querySelector('.toast-wrap') || (function(){ const w = document.createElement('div'); w.className = 'toast-wrap'; document.body.appendChild(w); return w; })(); const t = document.createElement('div'); t.className = 'toast' + (type === 'error' ? ' error' : ''); t.textContent = msg; wrap.appendChild(t); setTimeout(()=> t.remove(), 3500); }

  async function fetchLecturers(q){
    try{
      const res = await fetch('api/get_users.php');
      const json = await res.json();
      return json.ok ? json.data : [];
    }catch(e){ return []; }
  }

  async function fetchClasses(){
    try{ const res = await fetch('api/get_classes.php'); const json = await res.json(); return json.ok ? json.data : []; }catch(e){ return []; }
  }

  async function renderAdminLists(){
    const q = (searchLecturer?.value || '').toLowerCase();
    const users = await fetchLecturers(q);
    const classes = await fetchClasses();

    if(lecturersGrid){
      lecturersGrid.innerHTML = '';
      users.forEach(u=>{
        const div = document.createElement('div'); div.className = 'entity-row';
        div.innerHTML = `<div class="entity-meta"><strong>${u.name}</strong><small class="muted">NIDN: ${u.nidn} • ${u.role}</small></div><div class="entity-actions"><button class="btn btn-small" data-edit-user="${u.id}">Edit</button><button class="btn btn-small" data-del-user="${u.id}">Hapus</button><a class="btn" href="admin-lecturer.php?lecturer=${u.id}">Lihat</a></div>`;
        lecturersGrid.appendChild(div);
      });
    }
    if(classesGrid){ classesGrid.innerHTML = ''; classes.forEach(c=>{ const div = document.createElement('div'); div.className = 'entity-row'; div.innerHTML = `<div class="entity-meta"><strong>${c.code}</strong><small class="muted">${c.name} • ${c.schedule}</small></div><div class="entity-actions"><button class="btn btn-small" data-edit-class="${c.id}">Edit</button><button class="btn btn-small" data-del-class="${c.id}">Hapus</button></div>`; classesGrid.appendChild(div); }); }

    // wire actions
    document.querySelectorAll('[data-edit-user]').forEach(btn=> btn.addEventListener('click', e=> openEditUser(btn.getAttribute('data-edit-user'))));
    document.querySelectorAll('[data-del-user]').forEach(btn=> btn.addEventListener('click', e=> confirmDeleteUser(btn.getAttribute('data-del-user'))));
    document.querySelectorAll('[data-edit-class]').forEach(btn=> btn.addEventListener('click', e=> openEditClass(btn.getAttribute('data-edit-class'))));
    document.querySelectorAll('[data-del-class]').forEach(btn=> btn.addEventListener('click', e=> confirmDeleteClass(btn.getAttribute('data-del-class'))));
  }

  searchLecturer?.addEventListener('input', renderAdminLists);
  refreshList?.addEventListener('click', renderAdminLists);

  document.getElementById('btnAddLecturer')?.addEventListener('click', async ()=>{
    const users = await fetchLecturers();
    showModal(`
      <h3>Tambah Dosen</h3>
      <form id="formAddUser">
        <label>NIDN <input name="nidn" required></label>
        <label>Nama <input name="name" required></label>
        <label>Password <input name="password" type="password" required></label>
        <label>Role <select name="role"><option value="dosen">Dosen</option><option value="admin">Admin</option></select></label>
        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end"><button type="submit" class="btn btn-primary">Simpan</button><button type="button" class="btn" data-close="true">Batal</button></div>
      </form>
    `);
    document.getElementById('formAddUser')?.addEventListener('submit', async function(e){ e.preventDefault(); const f = new FormData(this); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_user_create.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Dosen ditambahkan'); closeModal(); renderAdminLists(); } else { toast(j.error||'Gagal', 'error'); } }catch(e){ toast('Gagal (network)','error'); } });
  });

  document.getElementById('btnAddClass')?.addEventListener('click', async ()=>{
    const users = await fetchLecturers();
    const options = users.map(u=>`<option value="${u.id}">${u.name}</option>`).join('');
    showModal(`
      <h3>Tambah Kelas</h3>
      <form id="formAddClass">
        <label>Kode <input name="code" required></label>
        <label>Nama <input name="name" required></label>
        <label>Pengajar <select name="lecturer_id">${options}</select></label>
        <label>Jadwal <input name="schedule" placeholder="Senin, 08:00 - 09:30"></label>
        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end"><button type="submit" class="btn btn-primary">Simpan</button><button type="button" class="btn" data-close="true">Batal</button></div>
      </form>
    `);
    document.getElementById('formAddClass')?.addEventListener('submit', async function(e){ e.preventDefault(); const f = new FormData(this); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_class_create.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Kelas ditambahkan'); closeModal(); renderAdminLists(); } else { toast(j.error||'Gagal', 'error'); } }catch(e){ toast('Gagal (network)','error'); } });
  });

  async function openEditUser(id){
    // fetch user details
    try{ const r = await fetch(`api/get_users.php?id=${id}`); const j = await r.json(); if(!j.ok){ toast('User tidak ditemukan','error'); return; } const u = j.data[0]; showModal(`
      <h3>Edit Dosen</h3>
      <form id="formEditUser">
        <input type="hidden" name="id" value="${u.id}">
        <label>Nama <input name="name" required value="${u.name}"></label>
        <label>Password (kosong = tidak diubah) <input name="password" type="password"></label>
        <label>Role <select name="role"><option value="dosen" ${u.role==='dosen'?'selected':''}>Dosen</option><option value="admin" ${u.role==='admin'?'selected':''}>Admin</option></select></label>
        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end"><button type="submit" class="btn btn-primary">Simpan</button><button type="button" class="btn" data-close="true">Batal</button></div>
      </form>
    `); document.getElementById('formEditUser')?.addEventListener('submit', async function(e){ e.preventDefault(); const f = new FormData(this); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_user_update.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Dosen diperbarui'); closeModal(); renderAdminLists(); } else { toast(j.error||'Gagal','error'); } }catch(e){ toast('Gagal (network)','error'); } }); }catch(e){ toast('Gagal (network)','error'); }}

  async function confirmDeleteUser(id){ if(!confirm('Hapus dosen ini?')) return; const f = new FormData(); f.append('id', id); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_user_delete.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Dosen dihapus'); renderAdminLists(); } else { toast(j.error||'Gagal','error'); } }catch(e){ toast('Gagal (network)','error'); } }

  async function openEditClass(id){ try{ const r = await fetch(`api/get_classes.php`); const j = await r.json(); const classes = j.data; const c = classes.find(x=>String(x.id) === String(id)); if(!c){ toast('Kelas tidak ditemukan','error'); return;} const users = await fetchLecturers(); const options = users.map(u=>`<option value="${u.id}" ${u.id==c.lecturer_id?'selected':''}>${u.name}</option>`).join(''); showModal(`
      <h3>Edit Kelas</h3>
      <form id="formEditClass">
        <input type="hidden" name="id" value="${c.id}">
        <label>Kode <input name="code" required value="${c.code}"></label>
        <label>Nama <input name="name" required value="${c.name}"></label>
        <label>Pengajar <select name="lecturer_id">${options}</select></label>
        <label>Jadwal <input name="schedule" value="${c.schedule}"></label>
        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end"><button type="submit" class="btn btn-primary">Simpan</button><button type="button" class="btn" data-close="true">Batal</button></div>
      </form>
    `); document.getElementById('formEditClass')?.addEventListener('submit', async function(e){ e.preventDefault(); const f = new FormData(this); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_class_update.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Kelas diperbarui'); closeModal(); renderAdminLists(); } else { toast(j.error||'Gagal','error'); } }catch(e){ toast('Gagal (network)','error'); } }); }catch(e){ toast('Gagal (network)','error'); } }

  async function confirmDeleteClass(id){ if(!confirm('Hapus kelas ini?')) return; const f = new FormData(); f.append('id', id); f.append('csrf_token', window.AAU_CSRF_TOKEN||''); try{ const r = await fetch('api/admin_class_delete.php',{method:'POST', body: f}); const j = await r.json(); if(j.ok){ toast('Kelas dihapus'); renderAdminLists(); } else { toast(j.error||'Gagal','error'); } }catch(e){ toast('Gagal (network)','error'); } }

  // initial render for admin lists if page has them
  if(lecturersGrid || classesGrid) renderAdminLists();

  // Admin-wide presensi notifier: poll for recent presensi and notify admin
  if(lecturersGrid){
    (async function(){
      try{
        // initialize latest id
        const init = await fetch('api/get_recent_presensi.php?since=0');
        const initJson = await init.json();
        let latestId = 0;
        if(initJson && initJson.ok && initJson.data && initJson.data.length){ latestId = Math.max(...initJson.data.map(r=>r.id)); }

        setInterval(async function(){
          try{
            const res = await fetch(`api/get_recent_presensi.php?since=${latestId}`);
            const j = await res.json();
            if(j && j.ok && j.data && j.data.length){
              j.data.forEach(row => {
                toast(`${row.lecturer}: ${row.status} — ${row.kelas} ${row.date} ${row.time||''}`);
                if(row.id > latestId) latestId = row.id;
              });
              // refresh lists to reflect any changes
              renderAdminLists();
            }
          }catch(e){ /* ignore errors */ }
        }, 10000);
      }catch(e){ /* ignore init errors */ }
    })();
  }


  // Admin lecturer page: aggregate rekap for a lecturer
  const aggBody = document.getElementById('aggBody');
  const lecturerName = document.getElementById('lecturerName');
  const lecturerMeta = document.getElementById('lecturerMeta');
  const aggTotalEl = document.getElementById('aggTotal');
  const salaryAmount = document.getElementById('salaryAmount');
  const exportAgg = document.getElementById('exportAgg');
  const clearAgg = document.getElementById('clearAgg');

  if(aggBody && lecturerName){
    const lid = getQueryParam('lecturer') || window.AAU_LECTURER_ID;

    // prefer server API to get aggregated rekap and poll for new entries
    (async function(){
      async function fetchRekap(){
        const res = await fetch(`api/get_lecturer_rekap.php?lecturer=${encodeURIComponent(lid)}`);
        return await res.json();
      }

      try{
        const json = await fetchRekap();
        if(json && json.ok){
          lecturerName.textContent = json.lecturer.name;
          lecturerMeta.textContent = `NIDN: ${json.lecturer.nidn}`;
          const rows = json.rows || [];
          aggBody.innerHTML = '';
          rows.forEach(r=>{
            const tr = document.createElement('tr'); tr.dataset.id = r.id; tr.innerHTML = `<td>${r.kelas}</td><td>${r.date}</td><td>${r.time||'-'}</td><td>${r.status}</td>`; aggBody.appendChild(tr);
          });
          aggTotalEl.textContent = json.hadir || 0;
          salaryAmount.textContent = (json.salary || 0).toLocaleString('id-ID');

          // export behavior
          exportAgg?.addEventListener('click', function(){
            let csv = 'Kelas,Tanggal,Waktu,Status\n'; rows.forEach(r=> csv += `${r.kelas},${r.date},${r.time||''},${r.status}\n`);
            const blob = new Blob([csv], {type:'text/csv'}); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `rekap_${lid}.csv`; document.body.appendChild(a); a.click(); setTimeout(()=>{ URL.revokeObjectURL(url); a.remove(); },1000);
          });

          // clear behavior (with CSRF)
          clearAgg?.addEventListener('click', async function(){
            if(!confirm('Hapus semua rekap dosen ini?')) return;
            try{
              const body = new FormData(); body.append('lecturer', lid);
              if(window.AAU_CSRF_TOKEN) body.append('csrf_token', window.AAU_CSRF_TOKEN);
              const r = await fetch('api/clear_presensi.php', {method:'POST', body});
              const j = await r.json(); if(j && j.ok){ alert('Rekap terhapus: '+j.deleted); location.reload(); } else { alert('Gagal menghapus'); }
            }catch(e){ alert('Gagal menghapus (network)'); }
          });

          // set of seen IDs for delta detection
          let seen = new Set(rows.map(r => String(r.id)));

          // poll for updates every 10s
          setInterval(async function(){
            try{
              const j = await fetchRekap();
              if(j && j.ok){
                const latest = j.rows || [];
                const newRows = latest.filter(r => !seen.has(String(r.id)));
                if(newRows.length){
                  // prepend new rows (older-to-newer order preserved by reversing)
                  newRows.slice().reverse().forEach(r => {
                    const tr = document.createElement('tr'); tr.dataset.id = r.id; tr.innerHTML = `<td>${r.kelas}</td><td>${r.date}</td><td>${r.time||'-'}</td><td>${r.status}</td>`; aggBody.prepend(tr);
                    seen.add(String(r.id));
                  });

                  // update totals
                  const addHadir = newRows.filter(r=>r.status === 'Hadir').length;
                  const current = parseInt(aggTotalEl.textContent || '0', 10);
                  const updated = current + addHadir;
                  aggTotalEl.textContent = updated;
                  salaryAmount.textContent = (updated * 200000).toLocaleString('id-ID');

                  // notify admin with toast(s)
                  newRows.forEach(r => toast(`${r.kelas}: ${r.status} — ${r.date} ${r.time||''}`));
                }
              }
            }catch(e){ /* ignore polling errors silently */ }
          }, 10000);

          return;
        }
      }catch(e){ /* fallback next */ }

      // fallback: use earlier client-side aggregation
      const lecturer = getLecturer(lid);
      if(!lecturer){ lecturerName.textContent = 'Dosen tidak ditemukan'; return; }
      lecturerName.textContent = lecturer.name;
      lecturerMeta.textContent = `Mata kuliah: ${lecturer.classes.join(', ')}`;

      // gather rows locally
      let rows = [];
      lecturer.classes.forEach(cls =>{
        const key = `aau-rekap-${cls}`;
        const raw = localStorage.getItem(key);
        if(raw){
          try{ const arr = JSON.parse(raw); arr.forEach(r => rows.push(Object.assign({kelas: cls}, r))); }catch(e){}
        }
      });
      rows.sort((a,b)=> parseDateString(b.date) - parseDateString(a.date));
      aggBody.innerHTML = '';
      rows.forEach(r=>{ const tr = document.createElement('tr'); tr.innerHTML = `<td>${r.kelas}</td><td>${r.date}</td><td>${r.time}</td><td>${r.status}</td>`; aggBody.appendChild(tr); });
      const hadirCount = rows.filter(r=>r.status === 'Hadir').length; aggTotalEl.textContent = hadirCount; salaryAmount.textContent = (hadirCount * 200000).toLocaleString('id-ID');

      exportAgg?.addEventListener('click', function(){ let csv = 'Kelas,Tanggal,Waktu,Status\n'; rows.forEach(r=> csv += `${r.kelas},${r.date},${r.time},${r.status}\n`); const blob = new Blob([csv], {type:'text/csv'}); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `rekap_${lid}.csv`; document.body.appendChild(a); a.click(); setTimeout(()=>{ URL.revokeObjectURL(url); a.remove(); },1000); });

    })();
  }

  // Helper: get schedule info from page
  function getScheduleInfo(){
    let scheduleText = '';
    const scheduleEl = document.querySelector('.info-grid div:nth-child(2) div, [class*="schedule"]');
    if(scheduleEl) scheduleText = scheduleEl.textContent?.trim() || '';
    return scheduleText || 'jadwal yang ditentukan';
  }

  // Helper: generate error message based on schedule
  function getPresensiErrorMessage(){
    const scheduleText = getScheduleInfo();
    if(scheduleText.includes(':') && scheduleText.includes('-')){
      return `Presensi Belum Dibuka — Presensi hanya dapat dilakukan pada ${scheduleText}`;
    }
    return 'Presensi Belum Dibuka — Presensi hanya dapat dilakukan pada jam jadwal mengajar';
  }

  // wire simulate control
  const simulateToggle = document.getElementById('simulateToggle');
  const simulateTime = document.getElementById('simulateTime');
  if(simulateToggle && simulateTime){
    simulateToggle.addEventListener('change', function(){ simulateTime.disabled = !this.checked;
      // reevaluate presensi availability
      if(isWithinPresensi()){
        presensiStatus.classList.remove('closed'); presensiStatus.classList.add('open'); presensiStatus.textContent = 'Presensi dibuka — silakan pilih status dan simpan.'; saveBtn.disabled = false; saveBtn.classList.remove('button-disabled');
      } else { presensiStatus.classList.remove('open'); presensiStatus.classList.add('closed'); presensiStatus.textContent = getPresensiErrorMessage(); saveBtn.disabled = true; saveBtn.classList.add('button-disabled'); }
    });
    simulateTime.addEventListener('change', function(){ if(simulateToggle.checked) simulateToggle.dispatchEvent(new Event('change')); });
  }

  if(presensiStatus){
    if(isWithinPresensi()){
      presensiStatus.classList.add('open');
      presensiStatus.textContent = 'Presensi dibuka — silakan pilih status dan simpan.';
      saveBtn.disabled = false;
      saveBtn.classList.remove('button-disabled');
    } else {
      presensiStatus.classList.add('closed');
      presensiStatus.textContent = getPresensiErrorMessage();
      saveBtn.disabled = true;
      saveBtn.classList.add('button-disabled');
    }
  }

  if(presensiForm){
    presensiForm.addEventListener('change', function(){
      // enable save if within window
      if(isWithinPresensi()){
        saveBtn.disabled = false;
        saveBtn.classList.remove('button-disabled');
      }
    });
  }

  if(saveBtn){
    saveBtn.addEventListener('click', function(e){
      e.preventDefault();
      if(saveBtn.disabled) return;
      const selected = document.querySelector('input[name="status"]:checked');
      if(!selected){
        alert('Pilih salah satu status presensi.');
        return;
      }
      const now = new Date();
      const row = {date: now.toLocaleDateString('id-ID'), time: formatTime(now), status: selected.value};

      // try to save to server via API (if user is authenticated with PHP session)
      (async function(){
        try{
          const form = new FormData();
          form.append('status', selected.value);
          // prefer class id param if present in URL
          const params = new URLSearchParams(window.location.search);
          const classParam = params.get('class');
          if(classParam) form.append('class_id', classParam);
          else form.append('class_code', 'SERSAN-B'); // fallback code for demo

          // support simulate time
          const simulateToggle = document.getElementById('simulateToggle');
          const simulateTime = document.getElementById('simulateTime');
          if(simulateToggle && simulateToggle.checked && simulateTime && simulateTime.value){ form.append('simulate_time', simulateTime.value); }

          // include CSRF token when available
          if(window.AAU_CSRF_TOKEN) form.append('csrf_token', window.AAU_CSRF_TOKEN);

          const res = await fetch('api/save_presensi.php', { method: 'POST', body: form });
          const json = await res.json();

          // server-side validation or auth may return {ok:false, error:...}
          if(json && json.ok){
            // render server row
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${json.data.date}</td><td>${json.data.time}</td><td>${json.data.status}</td>`;
            rekapBody.prepend(tr);

            // update persisted storage as well (optional)
            rekapRows.unshift({date: json.data.date, time: json.data.time, status: json.data.status});
            saveRekap(rekapRows);
            updateTotal(rekapRows);

            saveBtn.textContent = 'Tersimpan ✓';
            setTimeout(()=> saveBtn.textContent = 'SIMPAN PRESENSI', 1500);
            return;
          }

          // handle server-side error (do not silently fallback)
          if(json && json.error){
            toast(json.error, 'error');
            return;
          }

        }catch(e){
          // network error - fallback to localStorage and notify user
          toast('Koneksi bermasalah, menyimpan secara lokal', 'error');
        }

        // fallback to client-only behavior (only on network errors)
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${row.date}</td><td>${row.time}</td><td>${row.status}</td>`;
        rekapBody.prepend(tr);
        rekapRows.unshift(row);
        saveRekap(rekapRows);
        updateTotal(rekapRows);

        saveBtn.textContent = 'Tersimpan ✓';
        setTimeout(()=> saveBtn.textContent = 'SIMPAN PRESENSI', 1500);
      })();
    });
  }

  // CSV export and clear
  const exportCsv = document.getElementById('exportCsv');
  const clearRekap = document.getElementById('clearRekap');
  if(exportCsv){
    exportCsv.addEventListener('click', function(){
      const rows = rekapRows.slice();
      let csv = 'Tanggal,Waktu Presensi,Status\n';
      rows.forEach(r=> csv += `${r.date},${r.time},${r.status}\n`);
      const blob = new Blob([csv], {type: 'text/csv'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href = url; a.download = `rekap_${currentClass}.csv`; document.body.appendChild(a); a.click(); setTimeout(()=>{ URL.revokeObjectURL(url); a.remove(); },1000);
    });
  }
  if(clearRekap){
    clearRekap.addEventListener('click', function(){
      if(!confirm('Hapus semua rekap untuk kelas ini (demo)?')) return;
      rekapRows = [];
      rekapBody.innerHTML = '';
      saveRekap(rekapRows);
      updateTotal(rekapRows);
    });
  }

  // Accessible menu toggle & logout
  document.querySelectorAll('.menu-toggle').forEach(btn => {
    const menuId = btn.getAttribute('aria-controls');
    const menu = document.getElementById(menuId);
    btn.addEventListener('click', function(e){
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      if(menu){
        menu.hidden = expanded;
        if(!expanded){
          // move focus to first item
          const focusable = menu.querySelector('a, button');
          if(focusable) focusable.focus();
        }
      }
    });
  });

  // Close menus with Escape or clicking outside
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
      document.querySelectorAll('.site-menu').forEach(menu => { menu.hidden = true; });
      document.querySelectorAll('.menu-toggle').forEach(btn => btn.setAttribute('aria-expanded','false'));
    }
  });
  document.addEventListener('click', function(e){
    document.querySelectorAll('.site-menu').forEach(menu => {
      const toggle = document.querySelector(`[aria-controls="${menu.id}"]`);
      if(toggle && !menu.contains(e.target) && !toggle.contains(e.target)){
        menu.hidden = true; toggle.setAttribute('aria-expanded','false');
      }
    });
  });

  document.querySelectorAll('.btn-logout').forEach(btn => btn.addEventListener('click', function(){
    // demo: clear session and go to login
    localStorage.removeItem('aau-presence');
    window.location.href = 'index.html';
  }));

});