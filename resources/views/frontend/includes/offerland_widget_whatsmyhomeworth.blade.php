<div class="offerland_widget_form_whatsmyhomeworth_wrapper" style="/*border: 1px solid #ddd;*/ text-align: center; margin-bottom: 10px;">
    <center>
    <div id='offervalue-embedded-form-container' />
 
       <script
  src="https://cdn.offerland.ca/widgets/offervalue.js"
  client-id="c368ebd4-7a44-4487-9e83-43061f8986d2"
  form-tagline = "Instant Home Evaluation"
   form-bg-color="#e5b021"
  form-shadow="0 4px 6px rgba(0, 0, 0, 0.1)"
  form-primary-color="#000000"
  form-secondary-color="#ffffff"

 ></script>
 </div>
 </center>
 {{--
 form-bg-color="#ffffff"
  form-shadow="0 4px 6px rgba(0, 0, 0, 0.1)"
  form-primary-color="#333333"
  form-secondary-color="#ffffff"
    theme="dark"
  --}}
  
 @if(1==0 && request()->input('offerland-test-widget')) 
<div id="offervalue-embedded-form-container" style="height:auto; width:100%; max-width:360; max-height:450px; "></div>
<script
  src="https://cdn.offerland.ca/widgets/offervalue.js"
  client-id="c368ebd4-7a44-4487-9e83-43061f8986d2"
  {{-- redirect-url="https://bccondosandhomes.com/" --}}
  form-bg-color="#ffffff"
  form-shadow="0 4px 6px rgba(0, 0, 0, 0.1)"
  form-primary-color="#333333"
  form-secondary-color="#ffffff"
  theme="dark"
  onload="setTimeout(()=>{
  	const el = document.querySelector('#offervalue-embedded-form-container > div');
  	el?.style.removeProperty('height');
  	el?.style.removeProperty('width');
  	el?.style.removeProperty('max-height');
  	el?.style.removeProperty('max-width');
	},1000)"
></script>
<script>
setTimeout(() => {
	const el = document.querySelector('#offervalue-embedded-form-container > div');
	el?.style.removeProperty('height');
	el?.style.removeProperty('width');
	el?.style.removeProperty('max-height');
	el?.style.removeProperty('max-width');
	const iframe = document.querySelector("#offerland-test-widget iframe");
	if (iframe) {
		const iframeDoc = iframe.contentWindow.document;
		const style = iframeDoc.createElement("style");
		{{-- style.innerHTML = `#offervalue-embedded-form-container { max-width:328px;max-height:420px; }`; --}}
		iframeDoc.head.appendChild(style); // Inject CSS into iframe
	}
}, 2000);
</script>

<script>
{{-- '.offrlnd-wdgt-plchlder-sm' --}}
	function moveOffrlndWhtsMyHmWorthWidget() {
		let widget = document.querySelector(".offerland_widget_form_whatsmyhomeworth_wrapper");
		let hiddenSm = document.querySelector('.offrlnd-wdgt-plchlder-big');
		let visibleXsSm = document.querySelector('.offrlnd-wdgt-plchlder-sm');

		if ({{-- window.getComputedStyle(hiddenSm).display === "none" || --}} jQuery('.offrlnd-wdgt-plchlder-sm').is(':visible')) {
			visibleXsSm.appendChild(widget);
		} else {
			hiddenSm.appendChild(widget);
		}
	}

	window.addEventListener("resize", moveOffrlndWhtsMyHmWorthWidget);
	window.addEventListener("load", moveOffrlndWhtsMyHmWorthWidget);

	// Observe for display changes
	const observer = new MutationObserver(() => moveOffrlndWhtsMyHmWorthWidget());
	observer.observe(document.querySelector(".hidden-sm"), { attributes: true, attributeFilter: ["style", "class"] });
	// Run on load
	document.addEventListener("DOMContentLoaded", moveOffrlndWhtsMyHmWorthWidget);
</script>

 @endif
</div>
