<!doctype html>
<html>
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>CSV Upload</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    .drop-zone { border:2px dashed #ccc; padding:2rem; text-align:center; cursor:pointer; }
    .drop-zone.dragover { border-color:#0d6efd; background:#f8f9ff; }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row mb-4">
      <div class="col-md-8 offset-md-2">
        <div class="card">
          <div class="card-body">
            <h4>Upload CSV</h4>
            @if(session('status'))
              <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form id="uploadForm" method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data">
              @csrf
              <div id="dropZone" class="drop-zone" tabindex="0">
                <p>Drop CSV here or click to choose</p>
                <input type="file" id="fileInput" name="file" accept=".csv" class="d-none">
                <div id="fileName" class="small text-muted mt-2"></div>
              </div>

              <div class="mt-3">
                <button class="btn btn-primary" type="submit">Upload</button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8 offset-md-2">
        <h5>Recent Uploads</h5>
        <div id="uploadsList">
          <!-- filled by JS -->
        </div>
      </div>
    </div>
  </div>

<script>
  // drag/drop UI
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('fileInput');
  const fileName = document.getElementById('fileName');

  dropZone.addEventListener('click', () => fileInput.click());
  dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length) {
      fileInput.files = files;
      fileName.textContent = files[0].name;
    }
  });

  fileInput.addEventListener('change', () => {
    fileName.textContent = fileInput.files[0]?.name || '';
  });

  // Polling for upload list every 2s
  async function loadUploads() {
    try {
      const res = await fetch('{{ route("api.uploads") }}', {
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) throw new Error('Fetch failed');
      const data = await res.json();
      renderUploads(data.data || data);
    } catch (err) {
      console.error(err);
    }
  }

  function renderUploads(items) {
    const el = document.getElementById('uploadsList');
    if (!items || items.length === 0) {
      el.innerHTML = '<div class="text-muted">No uploads yet</div>';
      return;
    }

    let html = '<div class="list-group">';
    items.forEach(u => {
      let badgeClass = 'secondary';
      if (u.status === 'processing') badgeClass = 'warning';
      if (u.status === 'completed') badgeClass = 'success';
      if (u.status === 'failed') badgeClass = 'danger';
      if (u.status === 'skipped') badgeClass = 'info';

      html += `<div class="list-group-item d-flex justify-content-between align-items-start">
        <div>
          <div class="fw-bold">${escapeHtml(u.filename)}</div>
          <div class="small text-muted">${u.uploaded_at}</div>
          <div class="small">${escapeHtml(u.message || '')}</div>
        </div>
        <div class="text-end">
          <span class="badge bg-${badgeClass}">${u.status}</span>
        </div>
      </div>`;
    });
    html += '</div>';
    el.innerHTML = html;
  }

  function escapeHtml(str = '') {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  // start polling
  loadUploads();
  setInterval(loadUploads, 2000);
</script>
</body>
</html>
