@extends('frontend.layouts.default')

@section('title')
    Complete your Profile | Hani & Les | BC Condos And Homes
@endsection

@push('before-styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  .cp-wrap *, .cp-wrap *::before, .cp-wrap *::after { box-sizing: border-box; }

  :root {
    --cp-dark:  #231f20;
    --cp-gold:  #e4b123;
    --cp-blue:  #22aae2;
    --cp-green: #1a7a3c;
    --cp-remax: #e31837;
  }

  .cp-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 120px);
    padding: 32px 16px;
    background: #f0ede8;
  }

  .cp-wrap {
    display: flex;
    width: 100%;
    max-width: 860px;
    min-height: 600px;
    border-radius: 12px;
    overflow: hidden;
    border: 0.5px solid rgba(0,0,0,0.12);
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    font-family: 'DM Sans', system-ui, sans-serif;
  }

  /* ── LEFT PANEL ── */
  .cp-panel-left {
    flex: 1.05;
    background: var(--cp-dark);
    padding: 40px 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .cp-panel-left::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    border-radius: 50%;
    border: 1px solid rgba(228,177,35,0.1);
    pointer-events: none;
  }

  .cp-panel-left::after {
    content: '';
    position: absolute;
    bottom: -40px; left: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    border: 1px solid rgba(34,170,226,0.08);
    pointer-events: none;
  }

  .cp-brand-row {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    z-index: 1;
  }

  .cp-remax-badge {
    background: var(--cp-remax);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    padding: 4px 10px;
    border-radius: 4px;
  }

  .cp-remax-label {
    font-size: 12px;
    color: rgba(255,255,255,0.45);
    font-weight: 300;
    letter-spacing: 0.04em;
  }

  .cp-agent-section {
    position: relative;
    z-index: 1;
  }

  .cp-agent-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 2.5px solid var(--cp-gold);
    background: #2d2925;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: var(--cp-gold);
    overflow: hidden;
    margin-bottom: 16px;
  }

  .cp-agent-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    border-radius: 50%;
    display: block;
  }

  .cp-agent-name {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: #fff;
    font-weight: 600;
    line-height: 1.15;
    margin-bottom: 4px;
  }

  .cp-agent-title {
    font-size: 12px;
    color: rgba(255,255,255,0.45);
    font-weight: 300;
    margin-bottom: 6px;
  }

  .cp-agent-rating {
    font-size: 12px;
    color: var(--cp-gold);
    margin-bottom: 20px;
  }

  .cp-contact-pills {
    display: flex;
    gap: 8px;
    margin-bottom: 28px;
    flex-wrap: wrap;
  }

  .cp-contact-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: rgba(255,255,255,0.75);
    background: rgba(255,255,255,0.07);
    border: 0.5px solid rgba(255,255,255,0.14);
    border-radius: 20px;
    padding: 6px 14px;
    text-decoration: none;
    transition: background 0.15s;
  }

  .cp-contact-pill:hover {
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.9);
  }

  .cp-contact-pill svg {
    width: 11px;
    height: 11px;
    fill: var(--cp-blue);
    flex-shrink: 0;
  }

  .cp-panel-divider {
    height: 0.5px;
    background: rgba(255,255,255,0.08);
    margin-bottom: 20px;
  }

  .cp-trust-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .cp-trust-item {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cp-trust-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--cp-blue);
    flex-shrink: 0;
  }

  .cp-trust-text {
    font-size: 12px;
    color: rgba(255,255,255,0.5);
  }

  .cp-panel-footer {
    font-size: 11px;
    color: rgba(255,255,255,0.18);
    font-weight: 300;
    position: relative;
    z-index: 1;
  }

  /* ── RIGHT PANEL ── */
  .cp-panel-right {
    flex: 1;
    background: #ffffff;
    padding: 40px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .cp-progress-bar {
    display: flex;
    gap: 4px;
    margin-bottom: 28px;
  }

  .cp-progress-seg {
    height: 2px;
    flex: 1;
    border-radius: 2px;
    background: #e5e3df;
  }

  .cp-progress-seg.done   { background: var(--cp-gold); }
  .cp-progress-seg.active { background: var(--cp-blue); }

  .cp-form-step-label {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--cp-blue);
    margin-bottom: 5px;
  }

  .cp-form-title {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    color: var(--cp-dark);
    font-weight: 600;
    margin-bottom: 4px;
  }

  .cp-form-desc {
    font-size: 14px;
    color: #888;
    font-weight: 300;
    margin-bottom: 20px;
  }

  .cp-alert {
    background: #fee;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: 12px;
    color: #721c24;
  }

  .cp-alert ul {
    margin: 0;
    padding-left: 16px;
  }

  .cp-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
  }

  .cp-field-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
  }

  .cp-field-label {
    font-size: 11px;
    font-weight: 500;
    color: #999;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .cp-field-input {
    height: 46px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0 14px;
    font-size: 15px;
    font-family: 'DM Sans', system-ui, sans-serif;
    color: var(--cp-dark);
    background: #fff;
    outline: none;
    width: 100%;
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  .cp-field-input:focus {
    border-color: var(--cp-blue);
    box-shadow: 0 0 0 3px rgba(34,170,226,0.12);
  }

  .cp-field-input.prefilled {
    background: #f9f8f6;
    color: #aaa;
  }

  .cp-user-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f7f6f3;
    border: 1px solid #e8e5e0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 18px;
  }

  .cp-user-pill-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--cp-blue);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .cp-user-pill-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    overflow: hidden;
  }

  .cp-user-pill-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--cp-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cp-user-pill-email {
    font-size: 12px;
    color: #999;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cp-phone-input-wrap {
    position: relative;
    flex: 1;
  }

  .cp-phone-input-wrap .cp-phone-cc {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 15px;
    color: #aaa;
    pointer-events: none;
    user-select: none;
  }

  .cp-phone-input-wrap .cp-field-input {
    padding-left: 38px;
  }

  .cp-consent-text {
    font-size: 13px;
    color: #999;
    line-height: 1.55;
    margin-bottom: 18px;
  }

  .cp-consent-link {
    color: var(--cp-blue);
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
  }

  .cp-consent-link:hover { text-decoration: underline; }

  .cp-submit-btn {
    width: 100%;
    height: 48px;
    background: var(--cp-blue);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: 15px;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.15s, opacity 0.15s, transform 0.1s;
  }

  .cp-submit-btn:hover:not(:disabled) { background: #1a90c2; }
  .cp-submit-btn:active:not(:disabled) { transform: scale(0.99); }

  .cp-submit-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }

  /* ── PHONE VERIFICATION ── */
  .cp-phone-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
  }

  .cp-send-btn {
    height: 46px;
    padding: 0 16px;
    background: var(--cp-blue);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: background 0.15s;
  }
  .cp-send-btn:hover:not(:disabled) { background: #1a90c2; }
  .cp-send-btn:disabled { opacity: 0.45; cursor: not-allowed; }

  .cp-otp-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
  }

  .cp-verified-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--cp-green);
    font-weight: 500;
    padding: 8px 0;
    margin-bottom: 14px;
  }

  .cp-field-hint {
    font-size: 13px;
    color: #aaa;
    line-height: 1.5;
    margin-top: -8px;
    margin-bottom: 12px;
  }

  .cp-field-error {
    font-size: 13px;
    color: #c0392b;
    margin-bottom: 10px;
    display: none;
  }

  @media (max-width: 640px) {
    .cp-wrap         { flex-direction: column; }
    .cp-panel-left   { padding: 32px 24px; }
    .cp-panel-right  { padding: 32px 24px; }
    .cp-field-row    { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
@php
$profileAgentName = 'Hani Faraj';
$profileAgentFirstName = 'Hani';
$profileAgentInitials = 'HF';
$profileAgentImage = asset('frontend/images/teamagents/hani_faraj.jpg');
$profileAgentTitle = 'BC Real Estate Specialist';
$profileAgentPhone = '604-229-3342';
$profileAgentTel = '+16042293342';
$profileAgentSms = '+16042293342';
@endphp

{{-- T&C Modal --}}
<div class="modal fade" id="tandcModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"></div>
      <div class="modal-body" style="padding-bottom:0">
        <div class="main" role="main" style="text-align: justify">
          <div class="col-md-12">
            <div class="legal__item" style="text-align: justify">
              <h1>Terms &amp; Conditions</h1>
              <div class="legal__updated">LAST UPDATED: January 5, 2024</div>
              <div>
                <div class="legal__paragraph">
                  <p>By accessing any of the websites or mobile applications operated by the agent you agree to be bound by all of the TERMS &amp; CONDITIONS and PRIVACY POLICY and agree that these terms constitute a binding contract between you and the agent.</p>
                </div>
                <div class="legal__paragraph">
                  <h2>Copyright</h2>
                  <p>Property listings and other data available on this site are intended for private, non-commercial use by individuals. Any commercial use of the listings or data in whole or in part, directly or indirectly, is specifically forbidden except with the prior written authority of the owner of the copyright.</p>
                  <p>The users may, subject to the Terms and Conditions, print or otherwise save individual pages for private use. However, property listings and/or data may not be modified or altered in any respect, merged with other data or published in any form, in whole or in part. The prohibited uses include "screen scraping," "database scraping," and any other activities intended to collect, store, reorganize or manipulate or publish data on the pages produced by, or displayed by this website or its associated or affiliated websites.</p>
                  <p>REALTOR® is a certification mark owned by REALTOR® Canada Inc., a corporation owned by the National Association of REALTORS® and CREA.</p>
                  <p>Multiple Listing Service® is a registered certification mark owned by CREA and used to identify real estate services provided by brokers and salespersons who are members of CREA.</p>
                </div>
                <div class="legal__paragraph">
                  <h2>Disclaimer</h2>
                  <p>We makes no representations about the suitability of the data, information, or graphics published on this site. Everything on this site is provided "As Is" and "As Available" without warranty of any kind including all implied warranties and conditions of merchantability, fitness for a particular purpose, title and non-infringement. Neither REALTOR®, Pixilink, Hani & Les | BC Condos And Homes nor any of its members, directors, officer, shareholders or affiliates shall be liable for any direct, incidental, consequential, indirect or punitive damages arising out of your access to or use of this site.</p>
                </div>
                <div class="legal__paragraph">
                  <h2>Privacy Notice and Consent</h2>
                  <p>In accordance with the Rules of Cooperation of the CADREB, FVREB, and REBGV, and in conjunction with Privacy Policy, the User acknowledges understanding of and agreement with the following:</p>
                  <ul>
                    <li>The Registrant has received, read and understood the brochure published by the British Columbia Real Estate Association entitled "Privacy Notice and Consent";</li>
                    <li>all data obtained from the MLS® VOW is intended for and may only be used for the User's personal, non-commercial use;</li>
                    <li>the Registrant has a bona fide interest in the purchase, sale or lease of real estate of the type being offered through the MLS® VOW;</li>
                    <li>the Registrant will not himself, and will not permit or assist others to, directly or indirectly:
                      <ul>
                        <li>copy, redistribute or retransmit any of the MLS® VOW Data or information provided;</li>
                        <li>display, post, disseminate, distribute, publish, broadcast, transfer, sell or sublicense any of the MLS® VOW Data to another person.</li>
                        <li>engage in Scraping (including "screen scraping" and "database scraping"), "data mining" or any other activity intended to collect, store, re-organize, summarize or manipulate any MLS® VOW Data or any related data;</li>
                      </ul>
                    </li>
                    <li>the Registrant acknowledges the Board's ownership of, and the validity of the Board's proprietary rights and copyright in the MLS® VOW Data, and listing information; and</li>
                    <li>the Registrant expressly authorizes the Board or their duly authorized representatives, to access the MLS® VOW and User's information provided to the MLS® VOW Participant, for the purposes of verifying compliance with and pursuing enforcement of the Terms of Use and all applicable rules, regulations, bylaws, policies, and laws.</li>
                    <li>Acknowledge and understand that the Terms of Use do not create an agency relationship and do not impose a financial obligation on the Registrant or create any representation agreement between the Registrant and the Participant;</li>
                    <li>Acknowledge and enter into a lawful REALTOR®/ consumer or REALTOR®/client relationship with the Participant, including, where necessary, completion of any applicable agency, non-agency, and other disclosure obligations, and execution of any required agreements;</li>
                    <li>Understand that information on this site is deemed to be valid but is not guaranteed. It is the responsibility of the registrants to confirm all information on their own.</li>
                  </ul>
                </div>
                <div class="legal__paragraph">
                  <h2>Commercial Electronic Messages ("CEMs")</h2>
                  <p>The REALTOR® will only sends CEMs, such as emails, in accordance with Canada&#39;s Anti-Spam Legislation ("CASL").</p>
                </div>
                <div class="legal__paragraph">
                  <h2>Third Party Websites</h2>
                  <p>The Website/App may contain links from other third party websites and all such websites are independent. REALTOR® has no control over these third party websites and assumes no responsibility or obligations for such third party websites. The provision of such links does not constitute any endorsement of such linked websites, their content or information appearing on the Website/App.</p>
                </div>
                <div class="legal__paragraph">
                  <h2>Jurisdiction</h2>
                  <p>The Website/App can be accessed from all provinces and territories of Canada, as well as from other countries around the world. As each of these jurisdictions has laws that may differ from those of the Province of British Columbia, by accessing the Website/App or Associated Services, you agree that all matters relating to access to, or use of, the Website/App or Associated Services, or any other hyperlinked website shall be governed by the laws of the Province of British Columbia and the federal laws of Canada as applicable and notwithstanding conflicts of law. You also agree and hereby submit to the exclusive jurisdiction and venue of the courts of the Province of British Columbia and acknowledge and do so voluntarily.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Page wrapper --}}
<div class="cp-page">
  <div class="cp-wrap">

    {{-- LEFT PANEL --}}
    <div class="cp-panel-left">

      <div class="cp-brand-row">
        <div class="cp-remax-badge">RE/MAX</div>
        <div class="cp-remax-label">Crest Westside</div>
      </div>

      <div class="cp-agent-section">

        <div class="cp-agent-photo">
          @if($profileAgentImage)
          <img src="{{ $profileAgentImage }}"
               alt="{{ $profileAgentName }}"
               onerror="this.style.display='none';this.parentElement.textContent='{{ $profileAgentInitials }}'">
          @else
          {{ $profileAgentInitials }}
          @endif
        </div>

        <div class="cp-agent-name">{{ $profileAgentName }}</div>
        <div class="cp-agent-title">{{ $profileAgentTitle }}</div>
        <div class="cp-agent-rating">&#9733; 4.9 &nbsp;&middot;&nbsp; 39 reviews</div>

        <div class="cp-contact-pills">
          <a class="cp-contact-pill" href="tel:{{ $profileAgentTel }}">
            <svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
            Call {{ $profileAgentFirstName }}
          </a>
          <a class="cp-contact-pill" href="sms:{{ $profileAgentSms }}">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
            Text {{ $profileAgentFirstName }}
          </a>
        </div>

        <div class="cp-panel-divider"></div>

        <div class="cp-trust-list">
          <div class="cp-trust-item">
            <div class="cp-trust-dot"></div>
            <span class="cp-trust-text">Ranked #1 &amp; #2 on Google for Vancouver condos</span>
          </div>
          <div class="cp-trust-item">
            <div class="cp-trust-dot"></div>
            <span class="cp-trust-text">350,000+ monthly visitors trust this platform</span>
          </div>
          <div class="cp-trust-item">
            <div class="cp-trust-dot"></div>
            <span class="cp-trust-text">Typically responds within 15 minutes</span>
          </div>
        </div>

      </div>

      <div class="cp-panel-footer">bccondosandhomes.com</div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="cp-panel-right">

      <div class="cp-progress-bar">
        <div class="cp-progress-seg done"></div>
        <div class="cp-progress-seg active"></div>
      </div>

      <div class="cp-form-step-label">Step 2 of 2 — Verify your phone</div>
      <div class="cp-form-title">One last step</div>
      <div class="cp-form-desc">Add your phone number to unlock sold prices and full market data.</div>

      @if ($errors->any())
        <div class="cp-alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="post" action="{{ route('complete-profile', request()->query->all()) }}" name="complete_profile" id="complete-profile">
        @csrf

        {{-- Hidden name/email fields still submitted for server-side processing --}}
        <input type="hidden" name="first_name" value="{{ $user->first ?? old('first_name') }}">
        <input type="hidden" name="last_name"  value="{{ $user->last  ?? old('last_name')  }}">
        <input type="hidden" name="email"       value="{{ $user->email ?? old('email')      }}">

        {{-- User identity confirmation pill --}}
        @php
          $cpInitials = strtoupper(substr($user->first ?? 'U', 0, 1) . substr($user->last ?? '', 0, 1));
          $cpFullName = trim(($user->first ?? '') . ' ' . ($user->last ?? ''));
          $cpEmail    = $user->email ?? '';
        @endphp
        <div class="cp-user-pill">
          <div class="cp-user-pill-avatar">{{ $cpInitials }}</div>
          <div class="cp-user-pill-info">
            <div class="cp-user-pill-name">{{ $cpFullName ?: 'Your Account' }}</div>
            <div class="cp-user-pill-email">{{ $cpEmail }}</div>
          </div>
        </div>

        {{-- Phone verification --}}
        <div class="cp-field-group">
          <label class="cp-field-label">Phone number</label>
          <p class="cp-field-hint">Required by Real Estate Board rules — gives you instant access to sold prices.</p>

          @if($user->phone_verified && $user->phone)
          {{-- Already verified — show badge immediately --}}
          <div class="cp-verified-badge" id="cp-verified-badge">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Phone verified ({{ $user->phone }})
          </div>
          <div class="cp-phone-row" id="cp-phone-entry" style="display:none"></div>
          <div class="cp-otp-row" id="cp-otp-entry" style="display:none"></div>
          <div class="cp-field-error" id="cp-phone-error"></div>
          <div class="cp-field-error" id="cp-otp-error"></div>
          <input type="hidden" id="cp-phone-input" value="{{ $user->phone }}">
          <input type="hidden" id="cp-otp-input" value="">
          @else
          {{-- Phone entry row --}}
          <div class="cp-phone-row" id="cp-phone-entry">
            <div class="cp-phone-input-wrap">
              <span class="cp-phone-cc">+1</span>
              <input class="cp-field-input" type="tel" id="cp-phone-input"
                     placeholder="___ ___ ____" autocomplete="off"
                     value="">
            </div>
            <button class="cp-send-btn" type="button" id="cp-send-code">Send Code</button>
          </div>
          <div class="cp-field-error" id="cp-phone-error"></div>

          {{-- OTP entry row (hidden until code sent) --}}
          <div class="cp-otp-row" id="cp-otp-entry" style="display:none">
            <input class="cp-field-input" type="text" id="cp-otp-input"
                   placeholder="Enter 6-digit code" maxlength="6"
                   style="flex:1;margin:0;letter-spacing:0.12em;font-size:16px">
            <button class="cp-send-btn" type="button" id="cp-verify-code">Verify</button>
            <button class="cp-send-btn" type="button" id="cp-change-number"
                    style="background:#f1f0ee;color:#555;">Change</button>
          </div>
          <div class="cp-field-error" id="cp-otp-error"></div>

          {{-- Verified state (hidden until verified) --}}
          <div class="cp-verified-badge" id="cp-verified-badge" style="display:none">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Phone verified
          </div>
          @endif
        </div>

        {{-- Hidden consent fields (pre-accepted) for server-side validation --}}
        <input type="hidden" name="agreePrivacyNotice"       value="on">
        <input type="hidden" name="agreeDisclosure"          value="on">
        <input type="hidden" name="agreeTermsAndConditions"  value="on">

        {{-- Hidden fields submitted with form --}}
        <input type="hidden" name="phone" id="cp-phone-hidden" value="{{ ($user->phone_verified && $user->phone) ? $user->phone : '' }}">
        <input type="hidden" name="country_code" value="+1">
        <input type="hidden" name="phone_verified" id="cp-phone-verified-flag" value="{{ ($user->phone_verified && $user->phone) ? '1' : '0' }}">
        <input type="hidden" name="redirect" value="{{ request()->get('redirect', '') }}">

        <p class="cp-consent-text">
          By registering you agree to our
          <a href="#" class="cp-consent-link" data-toggle="modal" data-target="#tandcModal">Privacy Notice</a>,
          <a href="#" class="cp-consent-link" data-toggle="modal" data-target="#tandcModal">Disclosure of Representation</a>,
          and <a href="#" class="cp-consent-link" data-toggle="modal" data-target="#tandcModal">Terms and Conditions</a>.
        </p>

        <button class="cp-submit-btn" id="cp-submit-btn" type="submit" disabled>
          Complete registration
        </button>

      </form>

    </div>

  </div>
</div>

@endsection

@push('after-scripts')
<script type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{!! $validator->selector('#complete-profile') !!}
<script>
  (function () {
    var btn = document.getElementById('cp-submit-btn');
    var phoneVerified = document.getElementById('cp-phone-verified-flag').value === '1';

    function updateBtn() {
      btn.disabled = !phoneVerified;
    }

    updateBtn();

    /* ── SEND CODE ── */
    var sendCodeEl = document.getElementById('cp-send-code');
    if (sendCodeEl) sendCodeEl.addEventListener('click', function () {
      var rawPhone = document.getElementById('cp-phone-input').value.replace(/\D/g, '');
      var phoneErr = document.getElementById('cp-phone-error');
      phoneErr.style.display = 'none';

      if (!rawPhone || rawPhone.length < 9) {
        phoneErr.textContent = 'Please enter a valid phone number.';
        phoneErr.style.display = 'block';
        return;
      }

      var sendBtn = this;
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending…';

      jQuery.ajax({
        method: 'post',
        @if(auth()->user()?->can('dev-dj'))
        url: '{{ route("test-post-confirm-phone-number") }}?action=send_verification_code',
        @else
        url: '{{ route("post-confirm-phone-number") }}?action=send_verification_code',
        @endif
        data: { number: rawPhone, country_code: '+1', '_token': '{{ csrf_token() }}' }
      }).done(function (response) {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Resend';
        if (response.success) {
          document.getElementById('cp-phone-hidden').value = rawPhone;
          document.getElementById('cp-phone-input').disabled = true;
          document.getElementById('cp-phone-entry').style.display = 'none';
          document.getElementById('cp-otp-entry').style.display = 'flex';
        } else {
          phoneErr.textContent = 'Unable to send code. Check the number and try again.';
          phoneErr.style.display = 'block';
        }
      }).fail(function () {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Code';
        phoneErr.textContent = 'Network error. Please try again.';
        phoneErr.style.display = 'block';
      });
    });

    /* ── CHANGE NUMBER ── */
    var changeNumEl = document.getElementById('cp-change-number');
    if (changeNumEl) changeNumEl.addEventListener('click', function () {
      document.getElementById('cp-phone-input').disabled = false;
      document.getElementById('cp-phone-entry').style.display = 'flex';
      document.getElementById('cp-otp-entry').style.display = 'none';
      document.getElementById('cp-verified-badge').style.display = 'none';
      document.getElementById('cp-otp-input').value = '';
      document.getElementById('cp-phone-hidden').value = '';
      document.getElementById('cp-phone-verified-flag').value = '0';
      phoneVerified = false;
      updateBtn();
    });

    /* ── VERIFY CODE ── */
    var verifyCodeEl = document.getElementById('cp-verify-code');
    var cpVerifying = false;

    function cpDoVerify() {
      if (cpVerifying) return;
      var code = document.getElementById('cp-otp-input').value.replace(/\D/g, '');
      var otpErr = document.getElementById('cp-otp-error');
      otpErr.style.display = 'none';

      if (code.length !== 6) {
        if (!code) {
          otpErr.textContent = 'Please enter the verification code.';
          otpErr.style.display = 'block';
        }
        return;
      }

      cpVerifying = true;
      if (verifyCodeEl) { verifyCodeEl.disabled = true; verifyCodeEl.textContent = 'Verifying…'; }

      jQuery.ajax({
        method: 'post',
        @if(auth()->user()?->can('dev-dj'))
        url: '{{ route("test-post-confirm-phone-number") }}?action=verify_code',
        @else
        url: '{{ route("post-confirm-phone-number") }}?action=verify_code',
        @endif
        data: { code: code, '_token': '{{ csrf_token() }}' }
      }).done(function (response) {
        cpVerifying = false;
        if (verifyCodeEl) { verifyCodeEl.disabled = false; verifyCodeEl.textContent = 'Verify'; }
        if (response.success) {
          document.getElementById('cp-otp-entry').style.display = 'none';
          document.getElementById('cp-verified-badge').style.display = 'flex';
          document.getElementById('cp-phone-verified-flag').value = '1';
          phoneVerified = true;
          try {
            var _cpPhone = document.getElementById('cp-phone-hidden').value || null;
            fetch('https://admin.bccondosandhomes.com/api/track/identify', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-Track-Key': 'intercomsucks5998436' },
              body: JSON.stringify({
                email: '{{ auth()->user()->email ?? '' }}',
                phone: _cpPhone,
                city: window._bccPageCity || null,
                anonymousId: document.cookie.match(/bc_anon_id=([^;]+)/)?.[1] || null
              })
            });
          } catch(e) {}
          updateBtn();
        } else {
          otpErr.textContent = 'Incorrect code. Please try again.';
          otpErr.style.display = 'block';
        }
      }).fail(function () {
        cpVerifying = false;
        if (verifyCodeEl) { verifyCodeEl.disabled = false; verifyCodeEl.textContent = 'Verify'; }
        otpErr.textContent = 'Network error. Please try again.';
        otpErr.style.display = 'block';
      });
    }

    if (verifyCodeEl) verifyCodeEl.addEventListener('click', function () { cpDoVerify(); });

    var otpInputEl = document.getElementById('cp-otp-input');
    if (otpInputEl) otpInputEl.addEventListener('input', function () {
      if (this.value.replace(/\D/g, '').length === 6) cpDoVerify();
    });

    /* ── GA4: profile-completion event ── */
    var cpForm = document.getElementById('complete-profile');
    if (cpForm) {
      cpForm.addEventListener('submit', function () {
        try {
          var signInMethod = sessionStorage.getItem('bc_sign_in_method') || '';
          if (typeof gtag === 'function') {
            gtag('event', 'bc_profile_complete', { sign_in_method: signInMethod });
          }
        } catch (e) {}
      });
    }
  })();

</script>
@endpush
