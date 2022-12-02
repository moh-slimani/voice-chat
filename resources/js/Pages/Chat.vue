<script setup lang="ts">
import {Head, Link} from '@inertiajs/inertia-vue3'
import {Person} from "@/types/Person";
import {onMounted, reactive, ref} from "vue";

const props = defineProps<{
    person: Person,
    randomPerson: Person,
}>()

const callData = reactive<{
    otherPerson?: Person,
    me: Person,
}>({
    me: props.person
})

const createPeerConnection = () => {
    return new RTCPeerConnection({
        iceServers: [
            {
                urls: "stun:stun.stunprotocol.org"
            }
        ]
    });
};

const peer = createPeerConnection();

const constraints = {
    audio: true,
    video: true
};
const localVideo = ref<HTMLVideoElement>()
const remoteVideo = ref<HTMLVideoElement>()
const callButton = ref<HTMLButtonElement>()

const call = async () => {
    if (callButton.value) {
        callButton.value.disabled = true;
    }

    const localPeerOffer = await peer.createOffer();
    await peer.setLocalDescription(new RTCSessionDescription(localPeerOffer));

    console.log(localPeerOffer)

    await window.axios.post(`/api/call/${props.randomPerson.username}`, {
        me: callData.me.username,
        to: props.randomPerson.username,
        offer: JSON.stringify(localPeerOffer)
    })
};

const onMediaOffer = async (data: { offer: string, from: Person }) => {

    console.log(data)
    callData.otherPerson = data.from

    try {
        await peer.setRemoteDescription(new RTCSessionDescription(JSON.parse(data.offer)));
        const peerAnswer = await peer.createAnswer();
        await peer.setLocalDescription(new RTCSessionDescription(peerAnswer));

        console.log(peerAnswer)

        await window.axios.post(`/api/answer/${props.randomPerson.username}`, {
            me: callData.me.username,
            to: callData.otherPerson.username,
            answer: JSON.stringify(peerAnswer)
        })

    } catch (error) {
        console.log(error)
    }
};

const onMediaAnswer = async (data: { answer: string, from: Person }) => {
    console.log(data)
    callData.otherPerson = data.from
    await peer.setRemoteDescription(new RTCSessionDescription(JSON.parse(data.answer)));
};

const onIceCandidateEvent = async (event: RTCPeerConnectionIceEvent) => {

    await window.axios.post(`/api/candidate/${props.randomPerson.username}`, {
        me: callData.me.username,
        to: callData.otherPerson?.username,
        candidate: JSON.stringify(event.candidate)
    })
};

peer.onicecandidate = onIceCandidateEvent;

const onRemotePeerIceCandidate = async (data: { candidate: string, from: Person }) => {
    try {
        const candidate = new RTCIceCandidate(JSON.parse(data.candidate));
        await peer.addIceCandidate(candidate);
    } catch (error) {
        // Handle error
    }
};

const gotRemoteStream = (event: RTCTrackEvent) => {
    const [stream] = event.streams;
    if (remoteVideo.value)
        remoteVideo.value.srcObject = stream;
};

peer.addEventListener('track', gotRemoteStream);


onMounted(async () => {
    const stream = await navigator.mediaDevices.getUserMedia(constraints);
    if (localVideo.value) {
        localVideo.value.srcObject = stream;
    }

    stream.getTracks().forEach(track => peer.addTrack(track, stream));

    window.Echo.channel(`call.${props.person.username}`)
        .listen('.call.requested', (e: { offer: string, from: Person }) => {
            onMediaOffer(e)
        })
        .listen('.call.answered', (e: { answer: string, from: Person }) => {
            onMediaAnswer(e)
        })
        .listen('.call.candidate.sent', (e: { candidate: string, from: Person }) => {
            onRemotePeerIceCandidate(e)
        })
})


</script>

<template>
    <Head>
        <title>
            Video Chat
        </title>
    </Head>

    <div class="h-screen p-6 w-screen flex flex-col">

        <div class="grow">
            {{ randomPerson }}

            <div>
                My user id:
                <div id="userId"></div>
            </div>

            <div>
                Remote camera:
                <video ref="remoteVideo" id="remoteVideo" playsinline autoplay></video>
            </div>

            <div>
                My camera:
                <video ref="localVideo" id="localVideo" playsinline autoplay muted></video>
            </div>

        </div>

        <div class="flex flex-row items-center justify-center py-4 space-x-12">
            <Link class="bg-red-500 h-16 w-16 rounded-full flex justify-center items-center
                    text-white shadow shadow-md shadow-gray-500"
                  :href="$route('chat', person.username)">
                <i class="ri-refresh-line text-3xl align-middle"></i>
            </Link>
            <button @click="call"
                    class="bg-green-500 h-16 w-16 rounded-full text-white shadow shadow-md shadow-gray-500">
                <i class="ri-phone-line text-3xl align-middle"></i>
            </button>
        </div>

    </div>

</template>
