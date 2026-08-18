{{--
  W3 — Mortgage Pre-Qualification lead form.
  Variables:
    $agent — Agent model
--}}
<div class="lead-form" id="w3-form-wrap">
  <div class="lead-form__title">{{ $formHeading ?? 'Know Your Number Before You Shop' }}</div>
  <div class="lead-form__sub">{{ $formSub ?? 'Connect with a licensed BC mortgage broker. No hard credit pull until you\'re ready. Results in 24 hours.' }}</div>

  <div id="w3-success" class="lead-form__success">
    <div class="lead-form__success-icon">✓</div>
    <div style="font-weight:700;font-size:16px;margin-bottom:8px;">Request received!</div>
    <div style="color:var(--muted);font-size:14px;">One of {{ explode(' ', $agent->name)[0] }}'s mortgage partners will be in touch within 24 hours.</div>
  </div>

  <form id="w3-form" action="{{ route('agent.lead.store', $agent->slug) }}" method="POST" style="display:block;">
    @csrf
    <input type="hidden" name="form_type" value="w3">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="lead-form__row">
      <div>
        <label for="w3-name">Your Name</label>
        <input type="text" id="w3-name" name="name" placeholder="Jane Smith" required autocomplete="name">
      </div>
      <div>
        <label for="w3-phone">Phone</label>
        <input type="tel" id="w3-phone" name="phone" placeholder="604-555-0100" autocomplete="tel">
      </div>
    </div>

    <div class="lead-form__row">
      <label for="w3-email">Email</label>
      <input type="email" id="w3-email" name="email" placeholder="jane@example.com" required autocomplete="email">
    </div>

    <div class="lead-form__row">
      <label for="w3-budget">Approximate Budget</label>
      <select id="w3-budget" name="budget">
        <option value="">Select range</option>
        <option>Under $500,000</option>
        <option>$500,000 – $750,000</option>
        <option>$750,000 – $1,000,000</option>
        <option>$1,000,000 – $1,500,000</option>
        <option>$1,500,000 – $2,000,000</option>
        <option>Over $2,000,000</option>
      </select>
    </div>

    <div class="lead-form__row">
      <label for="w3-timeline">Purchase Timeline</label>
      <select id="w3-timeline" name="timeline">
        <option value="">Select timeline</option>
        <option>As soon as possible</option>
        <option>1–3 months</option>
        <option>3–6 months</option>
        <option>6–12 months</option>
        <option>Just exploring</option>
      </select>
    </div>

    <button type="submit" class="lead-form__btn">Get Pre-Qualified</button>
    <div class="lead-form__disclaimer">No hard credit pull. By submitting you agree to be contacted by {{ $agent->name }} and their mortgage partners.</div>
  </form>
</div>

<script>
(function() {
  var form = document.getElementById('w3-form');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Sending…';
    fetch(form.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value, 'Accept': 'application/json' },
      body: new FormData(form)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.success) {
        form.style.display = 'none';
        document.getElementById('w3-success').style.display = 'block';
      } else {
        btn.disabled = false;
        btn.textContent = 'Get Pre-Qualified';
        alert(d.message || 'Please fill in all required fields.');
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.textContent = 'Get Pre-Qualified';
    });
  });
})();
</script>
