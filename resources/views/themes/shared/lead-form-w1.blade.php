{{--
  W1 — Showing Request form.
  Variables:
    $agent        — Agent model
    $formHeading  — optional heading override
    $formSub      — optional subheading override
    $listingSlug  — optional listing slug to pre-fill hidden field
--}}
<div class="lead-form" id="w1-form-wrap">
  <div class="lead-form__title">{{ $formHeading ?? 'Book a Showing' }}</div>
  <div class="lead-form__sub">{{ $formSub ?? 'Pick a time that works — ' . explode(' ', $agent->name)[0] . ' will confirm within 2 hours.' }}</div>

  <div id="w1-success" class="lead-form__success">
    <div class="lead-form__success-icon">✓</div>
    <div style="font-weight:700;font-size:16px;margin-bottom:8px;">Request received!</div>
    <div style="color:var(--muted);font-size:14px;">{{ explode(' ', $agent->name)[0] }} will be in touch shortly to confirm your showing.</div>
  </div>

  <form id="w1-form" action="{{ route('agent.lead.store', $agent->slug) }}" method="POST" style="display:block;">
    @csrf
    <input type="hidden" name="form_type" value="w1">
    @if(isset($listingSlug))
      <input type="hidden" name="listing_slug" value="{{ $listingSlug }}">
    @endif

    <div class="lead-form__row">
      <label for="w1-name">Your Name</label>
      <input type="text" id="w1-name" name="name" placeholder="Jane Smith" required autocomplete="name">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="lead-form__row">
      <div>
        <label for="w1-email">Email</label>
        <input type="email" id="w1-email" name="email" placeholder="jane@example.com" required autocomplete="email">
      </div>
      <div>
        <label for="w1-phone">Phone</label>
        <input type="tel" id="w1-phone" name="phone" placeholder="604-555-0100" autocomplete="tel">
      </div>
    </div>

    <div class="lead-form__row">
      <label for="w1-date">Preferred Date</label>
      <input type="date" id="w1-date" name="preferred_date" min="{{ date('Y-m-d') }}">
    </div>

    <div class="lead-form__row">
      <label for="w1-msg">Message (optional)</label>
      <textarea id="w1-msg" name="message" placeholder="Any questions or specific times that work?"></textarea>
    </div>

    <button type="submit" class="lead-form__btn">Request a Showing</button>
    <div class="lead-form__disclaimer">By submitting you agree to be contacted by {{ $agent->name }}. No obligation.</div>
  </form>
</div>

<script>
(function() {
  var form = document.getElementById('w1-form');
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
        document.getElementById('w1-success').style.display = 'block';
      } else {
        btn.disabled = false;
        btn.textContent = 'Request a Showing';
        alert(d.message || 'Please fill in all required fields.');
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.textContent = 'Request a Showing';
    });
  });
})();
</script>
