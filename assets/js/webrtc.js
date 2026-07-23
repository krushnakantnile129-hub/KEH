/* Knowledge Exchange Hub - Enhanced WebRTC Peer Connection with Screen Share & Controls */
document.addEventListener('DOMContentLoaded', function () {
    const videoContainer = document.getElementById('webrtcCallContainer');
    if (!videoContainer) return;

    const roomId = videoContainer.dataset.roomId;
    const currentUserId = parseInt(videoContainer.dataset.userId);
    const peerUserId = parseInt(videoContainer.dataset.peerId);
    const isCaller = videoContainer.dataset.isCaller === 'true';

    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const toggleMicBtn = document.getElementById('toggleMic');
    const toggleCamBtn = document.getElementById('toggleCam');
    const toggleScreenBtn = document.getElementById('toggleScreen');
    const toggleFullscreenBtn = document.getElementById('toggleFullscreen');
    const endCallBtn = document.getElementById('endCall');
    const callStatusText = document.getElementById('callStatusText');
    const callTimer = document.getElementById('callTimer');
    const videoGridElement = document.getElementById('videoGridElement');

    let localStream;
    let peerConnection;
    let lastSignalId = 0;
    let pollInterval;
    let timerInterval;
    let secondsElapsed = 0;
    let micEnabled = true;
    let camEnabled = true;
    let isScreenSharing = false;

    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    async function initWebRTC() {
        try {
            updateStatus('Requesting camera & microphone permissions...');
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            localVideo.srcObject = localStream;
            updateStatus('Media acquired. Initializing connection...');

            createPeerConnection();

            if (isCaller) {
                updateStatus('Creating call offer...');
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                sendSignal('offer', JSON.stringify(offer));
                updateStatus('Waiting for peer to answer...');
            } else {
                updateStatus('Waiting for caller offer...');
            }

            startPollingSignals();
            startTimer();
        } catch (err) {
            console.error('Error starting video call:', err);
            updateStatus('Error: Could not access camera/microphone. ' + err.message, 'text-danger');
        }
    }

    function createPeerConnection() {
        peerConnection = new RTCPeerConnection(rtcConfig);

        // Add local tracks to peer connection
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });

        // Handle remote stream arrival
        peerConnection.ontrack = event => {
            if (event.streams && event.streams[0]) {
                remoteVideo.srcObject = event.streams[0];
                updateStatus('Connected to peer!', 'text-success');
            }
        };

        // Handle local ICE candidates
        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                sendSignal('ice-candidate', JSON.stringify(event.candidate));
            }
        };

        peerConnection.onconnectionstatechange = () => {
            if (peerConnection.connectionState === 'connected') {
                updateStatus('Call Active', 'text-success');
            } else if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
                updateStatus('Call Disconnected', 'text-danger');
            }
        };
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            secondsElapsed++;
            const mins = String(Math.floor(secondsElapsed / 60)).padStart(2, '0');
            const secs = String(secondsElapsed % 60).padStart(2, '0');
            if (callTimer) {
                callTimer.innerHTML = `<i class="fas fa-clock text-warning me-1"></i> ${mins}:${secs}`;
            }
        }, 1000);
    }

    function sendSignal(type, data) {
        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('room_id', roomId);
        formData.append('receiver_id', peerUserId);
        formData.append('signal_type', type);
        formData.append('signal_data', data);

        fetch('/KEH/api/webrtc-signal.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) console.error('Signal send error:', res.error);
        })
        .catch(err => console.error('Signal fetch error:', err));
    }

    function startPollingSignals() {
        pollInterval = setInterval(async () => {
            try {
                const res = await fetch(`/KEH/api/webrtc-signal.php?action=poll&room_id=${roomId}&last_id=${lastSignalId}`);
                const data = await res.json();

                if (data.success && data.signals.length > 0) {
                    for (const sig of data.signals) {
                        lastSignalId = Math.max(lastSignalId, sig.id);
                        await handleIncomingSignal(sig);
                    }
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }, 1500);
    }

    async function handleIncomingSignal(sig) {
        const payload = JSON.parse(sig.signal_data);

        if (sig.signal_type === 'offer' && !isCaller) {
            updateStatus('Offer received. Answering call...');
            await peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            sendSignal('answer', JSON.stringify(answer));
        } else if (sig.signal_type === 'answer' && isCaller) {
            updateStatus('Answer received. Establishing stream...');
            await peerConnection.setRemoteDescription(new RTCSessionDescription(payload));
        } else if (sig.signal_type === 'ice-candidate') {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(payload));
            } catch (err) {
                console.error('Error adding ICE candidate:', err);
            }
        }
    }

    function updateStatus(msg, textClass = 'text-warning') {
        if (callStatusText) {
            callStatusText.className = 'small font-weight-bold ' + textClass;
            callStatusText.innerText = msg;
        }
    }

    // Toggle Microphone
    if (toggleMicBtn) {
        toggleMicBtn.addEventListener('click', () => {
            micEnabled = !micEnabled;
            localStream.getAudioTracks().forEach(track => track.enabled = micEnabled);
            toggleMicBtn.classList.toggle('btn-secondary', !micEnabled);
            toggleMicBtn.classList.toggle('btn-dark', micEnabled);
            toggleMicBtn.innerHTML = micEnabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash text-danger"></i>';
        });
    }

    // Toggle Camera
    if (toggleCamBtn) {
        toggleCamBtn.addEventListener('click', () => {
            camEnabled = !camEnabled;
            localStream.getVideoTracks().forEach(track => track.enabled = camEnabled);
            toggleCamBtn.classList.toggle('btn-secondary', !camEnabled);
            toggleCamBtn.classList.toggle('btn-dark', camEnabled);
            toggleCamBtn.innerHTML = camEnabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash text-danger"></i>';
        });
    }

    // Toggle Screen Share
    if (toggleScreenBtn) {
        toggleScreenBtn.addEventListener('click', async () => {
            try {
                if (!isScreenSharing) {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];

                    const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
                    if (sender) sender.replaceTrack(screenTrack);

                    localVideo.srcObject = screenStream;
                    isScreenSharing = true;
                    toggleScreenBtn.classList.add('btn-primary');

                    screenTrack.onended = () => {
                        stopScreenShare();
                    };
                } else {
                    stopScreenShare();
                }
            } catch (err) {
                console.error('Screen sharing error:', err);
            }
        });
    }

    function stopScreenShare() {
        if (!isScreenSharing) return;
        const videoTrack = localStream.getVideoTracks()[0];
        const sender = peerConnection.getSenders().find(s => s.track.kind === 'video');
        if (sender) sender.replaceTrack(videoTrack);

        localVideo.srcObject = localStream;
        isScreenSharing = false;
        if (toggleScreenBtn) toggleScreenBtn.classList.remove('btn-primary');
    }

    // Toggle Fullscreen
    if (toggleFullscreenBtn && videoGridElement) {
        toggleFullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                videoGridElement.requestFullscreen().catch(err => console.error(err));
            } else {
                document.exitFullscreen().catch(err => console.error(err));
            }
        });
    }

    // End Call
    if (endCallBtn) {
        endCallBtn.addEventListener('click', () => {
            if (peerConnection) peerConnection.close();
            if (localStream) localStream.getTracks().forEach(track => track.stop());
            clearInterval(pollInterval);
            clearInterval(timerInterval);
            window.location.href = '/KEH/my-sessions.php?msg=' + encodeURIComponent('Call ended.');
        });
    }

    initWebRTC();
});
