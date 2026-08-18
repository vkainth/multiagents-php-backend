{{-- BCTrack: pass Firebase-authenticated user email so browsing history ties to their lead profile --}}
<script>
  window.BCTrack = window.BCTrack || {};
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof firebase !== 'undefined' && firebase.auth) {
      firebase.auth().onAuthStateChanged(function (user) {
        if (user && user.email) {
          window.BCTrack.email = user.email;
        }
      });
    }
  });
</script>
