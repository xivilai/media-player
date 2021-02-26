// contain all JS effects of this plugin so we don't break sites
(function() {
    // window.load is unfortunately the best handler to attach to for allowing media-element-js to initialize
    window.addEventListener("load", function() {
        var buttons = document.createRange().createContextualFragment(
            document.querySelector("#playback-buttons-template").innerHTML
        );
        var els = [].slice.call( document.querySelectorAll( '.mejs-container' ) );

        els.forEach(function(elem, i) {
            var mediaTag = elem.querySelector('audio,video');

            // buttons for me-js element
            [].slice.call(
                buttons.querySelectorAll('.playback-rate-button')
            ).forEach(function(elem) {
                elem.setAttribute('aria-controls', mediaTag.id );
            });

            // parent for controls
            var controls = elem.querySelector('.fp-controls');
            if(controls) {
                var container = controls.querySelector('.fp-duration'),
                    hasSpeedControls = controls.querySelector('.playback-rate-button');

                // Guard to ensure that this only affects as-yet unaffected elements
                if (!hasSpeedControls) {
                    // insertAfter container
                    container.parentNode.insertBefore(buttons.cloneNode(true), container.nextSibling);
                }

                var frameStepBtns = document.createRange()
                    .createContextualFragment(document.querySelector("#frame-step-buttons-template").innerHTML);
                container = document.querySelector(".fp-controls .mejs-playback-buttons");
                container.parentNode.insertBefore(frameStepBtns.cloneNode(true), container.nextSibling);

                controls.querySelector('.frame-step-previous').addEventListener('click', ()=>{
                    mediaTag.currentTime -= 1/30;
                });

                controls.querySelector('.frame-step-next').addEventListener('click', ()=>{
                    mediaTag.currentTime += 1/30;
                });
            }
        });
    });

    
    [].slice.call( document.querySelectorAll( 'audio,video' ) ).forEach( function(elem) {
        // when media is loaded persist the playback speed currently selected
        elem.addEventListener('loadedmetadata', function(e) {
            var playlist = e.target.closest('.wp-playlist');
            var activeSpeed, rate;
            if(playlist) {
                // WordPress Playlist state restore selected speed
                activeSpeed = playlist.querySelector('.mejs-container .playback-rate-button.mejs-active');
                rate = activeSpeed.dataset.value;
            } else {
                // Any media-element, getting the first
                activeSpeed = document.querySelector('.playback-rate-button.mejs-active, .playback-rate-button.active-playback-rate');
                if(activeSpeed) {
                    rate = activeSpeed.dataset.value;
                }
            }

            // Guard against failing matchers. The DOM must be fulfilled, but this also means this part maybe doesn't need media-element-js
            if(!rate) { return; }

            // This is actually the magic. It's basically a more complex document.querySelector('video, audio').playbackRate
            e.target.playbackRate = rate;
        });
    });

    // AJAX / SPA supporting click bind handler
    // Uses data attribute and aria attribute as well as class-names from media-element-js & this plugin
    // because this binds to body it should always be available in a valid HTML page
    document.body.addEventListener('click', function(e) {
        // Because we're bound to body, we need to guard and only act on HTML elements with the right class
        if(!e.target || !e.target.classList.contains('playback-rate-button')) { return; }

        // We set aria attributes informing which DOMElement to control
        var targetId = e.target.getAttribute('aria-controls')
            mediaTag = document.getElementById(targetId);

        // Guard against failing matchers. The DOM must be fulfilled, but this also means this part maybe doesn't need media-element-js
        var rate = e.target.dataset.value;

        // Guard against failing matchers. The DOM must be fulfilled, but this also means this part maybe doesn't need media-element-js
        if(!rate) { return; }

        // This is actually the magic. It's basically a more complex document.querySelector('video, audio').playbackRate
        if(mediaTag) {
            mediaTag.playbackRate = rate;
        } else {
            [].slice.call(
                document.querySelectorAll('audio, video')
            ).forEach(function(elem){
                elem.playbackRate = rate;
            });
        }

        var mediaPlaybackContainer;
        if(mediaTag) { mediaPlaybackContainer = mediaTag.closest('.mejs-container'); }

        // This allows use outside of WordPress for this
        if(!mediaTag || !mediaPlaybackContainer) { mediaPlaybackContainer = mediaTag || document.body; }

        // Clear all active playback rate buttons for this element of the active class
        [].slice.call(
            mediaPlaybackContainer.querySelectorAll('.playback-rate-button')
        ).map(function(elem) {
            elem.classList.remove('mejs-active', 'active-playback-rate');
        });

        // Set the clicked element, or the matching to active rate to be active
        [].slice.call(
            mediaPlaybackContainer.querySelectorAll('.playback-rate-button')
        ).forEach(function(elem) {
            if(rate && elem.dataset.value == rate) {
                elem.classList.add('mejs-active', 'active-playback-rate');
            }
        });
    });
})();