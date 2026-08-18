{{--
  W2 — Home Evaluation form.
  Variables:
    $agent       — Agent model
    $neighbourhood — optional neighbourhood name hint
--}}
<div class="lead-form" id="w2-form-wrap">
  <div class="lead-form__title">{{ $formHeading ?? 'What\'s Your Home Worth?' }}</div>
  <div class="lead-form__sub">{{ $formSub ?? 'Free, data-backed evaluation within 6 hours — based on real sold comparables. No obligation.' }}</div>

  <div id="w2-success" class="lead-form__success">
    <div class="lead-form__success-icon">✓</div>
    <div style="font-weight:700;font-size:16px;margin-bottom:8px;">Evaluation request received!</div>
    <div style="color:var(--muted);font-size:14px;">{{ explode(' ', $agent->name)[0] }} will review recent sales and send you a data-backed valuation.</div>
  </div>

  <form id="w2-form" action="{{ route('agent.lead.store', $agent->slug) }}" method="POST" style="display:block;">
    @csrf
    <input type="hidden" name="form_type" value="w2">

    <div class="lead-form__row">
      <label for="w2-address">Property Address</label>
      <input type="text" id="w2-address" name="property_address" placeholder="{{ isset($neighbourhood) ? $neighbourhood . ', BC' : 'Your street address' }}" required>
    </div>

    <div class="lead-form__row">
      <label for="w2-type">Property Type</label>
      <select id="w2-type" name="property_type">
        <option value="">Select type</option>
        <option>Condo / Apartment</option>
        <option>Townhouse</option>
        <option>Detached House</option>
        <option>Other</option>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="lead-form__row">
      <div>
        <label for="w2-name">Your Name</label>
        <input type="text" id="w2-name" name="name" placeholder="Jane Smith" required autocomplete="name">
      </div>
      <div>
        <label for="w2-phone">Phone</label>
        <input type="tel" id="w2-phone" name="phone" placeholder="604-555-0100" autocomplete="tel">
      </div>
    </div>

    <div class="lead-form__row">
      <label for="w2-email">Email</label>
      <input type="email" id="w2-email" name="email" placeholder="jane@example.com" required autocomplete="email">
    </div>

    <div class="lead-form__row">
      <label for="w2-timeline">Selling Timeline</label>
      <select id="w2-timeline" name="timeline">
        <option value="">Select timeline</option>
        <option>As soon as possible</option>
        <option>1–3 months</option>
        <option>3–6 months</option>
        <option>6–12 months</option>
        <option>Just curious / not sure yet</option>
      </select>
    </div>

    <button type="submit" class="lead-form__btn">Get My Free Valuation</button>
    <div class="lead-form__disclaimer">By submitting you agree to be contacted by {{ $agent->name }}. No obligation, no pressure.</div>
  </form>
</div>

<script>
(function() {
  var form = document.getElementById('w2-form');
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
        document.getElementById('w2-success').style.display = 'block';
      } else {
        btn.disabled = false;
        btn.textContent = 'Get My Free Valuation';
        alert(d.message || 'Please fill in all required fields.');
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.textContent = 'Get My Free Valuation';
    });
  });
})();
</script>
