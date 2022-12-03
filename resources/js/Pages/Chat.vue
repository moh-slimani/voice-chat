<script setup lang="ts">
import {Head, Link} from '@inertiajs/inertia-vue3'
import {Person} from "@/types/Person";
import {onBeforeUnmount, onMounted, reactive, ref} from "vue";
import {AVMedia} from "vue-audio-visual";
import {Dialog, TransitionChild, TransitionRoot} from '@headlessui/vue'


const props = defineProps<{
    person: Person,
    randomPerson: Person,
}>()

const callData = reactive<{
    unavailable: boolean;
    randomPerson?: Person
    me: Person,
    lookingFor?: 'm' | 'f',
    callFrom?: Person
    callFromOffer?: string;
    otherPerson?: Person,
    localAudio?: MediaStream,
    remoteAudio?: MediaStream,
}>({
    unavailable: false,
    me: props.person,
    randomPerson: props.randomPerson
})

const cycleLookingFor = () => {
    switch (callData.lookingFor) {
        case "f":
            callData.lookingFor = "m"
            break;
        case "m":
            callData.lookingFor = undefined
            break;
        default:
            callData.lookingFor = "f"
    }
}

const createPeerConnection = () => {
    return new RTCPeerConnection({
        username: "c698af7009f3ddebd54b7953df2c583704c271a3c745055267ff633ebd8ff21c",
        iceServers: [{
            // @ts-ignore
            "url": "stun:global.stun.twilio.com:3478?transport=udp",
            "urls": "stun:global.stun.twilio.com:3478?transport=udp"
        }, {
            "credential": "MdI9NbpyquY/dzqXuO27Yc+fEdfl6Ree8CWbYz+X168=",
            "username": "c698af7009f3ddebd54b7953df2c583704c271a3c745055267ff633ebd8ff21c",
            // @ts-ignore
            "url": "turn:global.turn.twilio.com:3478?transport=udp",
            "urls": "turn:global.turn.twilio.com:3478?transport=udp"
        }, {
            "credential": "MdI9NbpyquY/dzqXuO27Yc+fEdfl6Ree8CWbYz+X168=",
            "username": "c698af7009f3ddebd54b7953df2c583704c271a3c745055267ff633ebd8ff21c",
            // @ts-ignore
            "url": "turn:global.turn.twilio.com:3478?transport=tcp",
            "urls": "turn:global.turn.twilio.com:3478?transport=tcp"
        }, {
            "credential": "MdI9NbpyquY/dzqXuO27Yc+fEdfl6Ree8CWbYz+X168=",
            "username": "c698af7009f3ddebd54b7953df2c583704c271a3c745055267ff633ebd8ff21c",
            // @ts-ignore
            "url": "turn:global.turn.twilio.com:443?transport=tcp",
            "urls": "turn:global.turn.twilio.com:443?transport=tcp"
        }],
    });
};

const peer = ref<RTCPeerConnection>(createPeerConnection());
const stream = ref<MediaStream>()

const constraints = {
    audio: true,
    video: false
};
const callButton = ref<HTMLButtonElement>()

const call = async () => {
    if (callButton.value) {
        callButton.value.disabled = true;
    }

    callData.otherPerson = callData.randomPerson

    if (callData.otherPerson) {
        const localPeerOffer = await peer.value.createOffer();

        await window.axios.post(`/api/call/${callData.otherPerson.username}`, {
            me: callData.me.username,
            to: callData.otherPerson.username,
            offer: JSON.stringify(localPeerOffer)
        }).then(async () => {
            await peer.value.setLocalDescription(new RTCSessionDescription(localPeerOffer));

            console.log(localPeerOffer)
        }).catch(() => {
            callData.unavailable = true
            resetPeer()
        })
    }
};

const answer = async () => {

    open.value = false

    callData.otherPerson = callData.callFrom
    callData.unavailable = false

    if (callData.callFromOffer && callData.callFrom) {
        try {
            await peer.value.setRemoteDescription(new RTCSessionDescription(JSON.parse(callData.callFromOffer)));
            const peerAnswer = await peer.value.createAnswer();
            await peer.value.setLocalDescription(new RTCSessionDescription(peerAnswer));

            console.log(peerAnswer)

            await window.axios.post(`/api/answer/${callData.otherPerson?.username}`, {
                me: callData.me.username,
                to: callData.otherPerson?.username,
                answer: JSON.stringify(peerAnswer)
            })

        } catch (error) {
            console.log(error)
        }
    }
};

const hangupIncoming = async () => {
    if (callData.callFrom) {
        await window.axios.post(`/api/hangup/${callData.me.username}`, {
            me: callData.me.username,
            to: callData.callFrom.username,
        })
    }

    open.value = false

    getAnOtherPerson()
};

const resetPeer = () => {
    open.value = false

    peer.value.close()


    callData.otherPerson = undefined
    callData.callFrom = undefined
    callData.callFromOffer = undefined

    callData.remoteAudio = undefined

    peer.value = createPeerConnection()

    peer.value.onicecandidate = onIceCandidateEvent;
    peer.value.addEventListener('track', gotRemoteStream);

    if (stream.value) {
        // @ts-ignore
        stream.value.getTracks().forEach(track => peer.value.addTrack(track, stream.value));
    }

    if (callButton.value) {
        callButton.value.disabled = false
    }
}

const hangupCurrent = async () => {
    if (callData.otherPerson) {
        await window.axios.post(`/api/hangup/${callData.me.username}`, {
            me: callData.me.username,
            to: callData.otherPerson.username,
        })
    }

    resetPeer()
    getAnOtherPerson()
};

const onCallEnded = async () => {
    resetPeer()
};

const onMediaAnswer = async (data: { answer: string, from: Person }) => {
    console.log(data)
    callData.otherPerson = data.from
    await peer.value.setRemoteDescription(new RTCSessionDescription(JSON.parse(data.answer)));
};

const onIceCandidateEvent = async (event: RTCPeerConnectionIceEvent) => {
    if (callData.otherPerson) {
        await window.axios.post(`/api/candidate/${callData.otherPerson.username}`, {
            me: callData.me.username,
            to: callData.otherPerson?.username,
            candidate: JSON.stringify(event.candidate)
        })
    }
};

peer.value.onicecandidate = onIceCandidateEvent;

const onRemotePeerIceCandidate = async (data: { candidate: string, from: Person }) => {
    try {
        const candidate = new RTCIceCandidate(JSON.parse(data.candidate));
        await peer.value.addIceCandidate(candidate);
    } catch (error) {
        // Handle error
    }
};

const gotRemoteStream = (event: RTCTrackEvent) => {
    const [stream] = event.streams;
    console.log('stream', stream)
    console.log('callData.remoteAudio', callData.remoteAudio)
    callData.remoteAudio = stream;
    console.log('callData.remoteAudio', callData.remoteAudio)
    console.log('callData.localAudio', callData.localAudio)

    //@ts-ignore
    document.querySelector('#remoteAudio').srcObject = stream;
};

peer.value.addEventListener('track', gotRemoteStream);

const refreshing = ref(false)

const getAnOtherPerson = () => {
    refreshing.value = true
    window.axios.get(`/api/refresh/${props.person.username}${callData.lookingFor ? '?g=' + callData.lookingFor : ''}`)
        .then(({data}) => {
            callData.randomPerson = data
            callData.unavailable = false
        })
        .finally(() => {
            refreshing.value = false
        })
}

const open = ref(false)
const showModal = () => {
    open.value = true
}

onMounted(async () => {
    stream.value = await navigator.mediaDevices.getUserMedia(constraints);
    // @ts-ignore
    document.querySelector('#localAudio').srcObject = stream;

    if (stream.value) {
        callData.localAudio = stream.value;
        // @ts-ignore
        stream.value.getTracks().forEach(track => peer.value.addTrack(track, stream.value));
    }

    window.Echo.channel(`call.${props.person.username}`)
        .listen('.call.requested', (e: { offer: string, from: Person }) => {
            callData.callFrom = e.from
            callData.callFromOffer = e.offer
            showModal()
        })
        .listen('.call.answered', (e: { answer: string, from: Person }) => {
            onMediaAnswer(e)
        })
        .listen('.call.candidate.sent', (e: { candidate: string, from: Person }) => {
            onRemotePeerIceCandidate(e)
        })
        .listen('.call.ended', (e: { from: Person }) => {
            onCallEnded()
        })


    setInterval(() => {
        window.axios.post(`/api/checkin/${props.person.username}`).then(() => {
            console.log('checked in')
        })
    }, 60000)
})

onBeforeUnmount(() => {
    window.Echo.leaveChannel(`call.${props.person.username}`)
})
</script>

<template>
    <Head>
        <title>
            Audio Chat
        </title>
    </Head>

    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="fixed z-10 inset-0 overflow-y-auto" @close="open = false">
            <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden" aria-hidden="true">&#8203;</span>
                <TransitionChild as="template" enter="ease-out duration-300"
                                 enter-from="opacity-0 translate-y-4"
                                 enter-to="opacity-100 translate-y-0" leave="ease-in duration-200"
                                 leave-from="opacity-100 translate-y-0"
                                 leave-to="opacity-0 translate-y-4">
                    <div
                        class="flex flex-row max-w-sm w-screen align-bottom bg-white rounded-lg px-4 py-6 overflow-hidden shadow-lg shadow-gray-400">
                        <div class="grow flex flex-col justify-center items-start">
                            <h3 class="text-blue-500 font-bold text-2xl">
                                @{{ callData.callFrom?.username }}
                            </h3>
                            <p class="text-blue-500 font-bold">
                                {{ callData.callFrom?.gender === 'm' ? 'male' : 'female' }}</p>
                        </div>
                        <div class="flex flex-row space-x-3 justify-between">
                            <button class="bg-red-500 h-14 w-14 rounded-full flex justify-center items-center
                                    text-white shadow shadow-md shadow-gray-500"
                                    @click="hangupIncoming">
                                <i class="ri-close-circle-line text-2xl align-middle"></i>
                            </button>
                            <button @click="answer"
                                    class="bg-green-500 h-14 w-14 rounded-full text-white shadow shadow-md shadow-gray-500">
                                <i class="ri-phone-line text-2xl align-middle"></i>
                            </button>
                        </div>
                    </div>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>

    <div class="h-screen max-w-lg mx-auto p-6 flex flex-col">

        <div class="grow flex flex-col justify-center space-y-6">

            <div v-if="callData.unavailable" class="h-16">
                <h3 class="text-red-500 font-bold text-2xl text-center">No longer available</h3>
            </div>
            <div v-else-if="callData.otherPerson" class="h-16">
                <h3 class="text-blue-500 font-bold text-2xl text-center">@{{ callData.otherPerson?.username }}</h3>
                <h3 class="text-blue-500 font-bold text-center">
                    {{ callData.otherPerson?.gender === 'm' ? 'male' : 'female' }}</h3>
            </div>
            <div v-else-if="callData.randomPerson" class="h-16">
                <h3 class="text-blue-500 font-bold text-2xl text-center">@{{ callData.randomPerson?.username }}</h3>
                <h3 class="text-blue-500 font-bold text-center">
                    {{ callData.randomPerson?.gender === 'm' ? 'male' : 'female' }}</h3>
            </div>
            <div v-else class="h-16">
                <h3 class="text-red-500 font-bold text-2xl text-center">No one is online</h3>
            </div>

            <div class="relative w-full h-12">
                <audio id="remoteAudio" class="hidden" autoplay></audio>
                <audio id="localAudio" class="hidden" autoplay muted></audio>

                <AVMedia v-if="callData.remoteAudio"
                         :key="callData.remoteAudio.id"
                         class="absolute w-full inset-0"
                         :media="callData.remoteAudio"
                         line-color="#000"
                         :line-width="1"
                         type="frequ"
                         frequ-direction="mo"></AVMedia>
                <AVMedia v-if="callData.localAudio"
                         :key="callData.localAudio.id"
                         class="absolute w-full inset-0"
                         :media="callData.localAudio"
                         line-color="#000"
                         :line-width="1"
                         type="frequ"
                         frequ-direction="mo"></AVMedia>
            </div>

        </div>

        <div class="py-6">
            <h3 class="text-gray-400 font-bold text-2xl text-center">@{{ person.username }}</h3>
        </div>

        <div class="flex flex-row items-center justify-center py-4 space-x-12">
            <button @click="cycleLookingFor"
                    class="bg-green-500 p-3 h-16 w-16 rounded-full text-white shadow shadow-md shadow-gray-500">
                <svg v-if="callData.lookingFor === 'm'"
                     class="ri-phone-line align-middle"
                     viewBox="0 -60 512.00066 512"
                     fill="white"
                     xmlns="http://www.w3.org/2000/svg">
                    <g id="g913"
                       transform="matrix(1.3075052,0,0,1.3075052,103.67441,-60.807305)">
                        <path
                            d="m 116.5,122.94922 c 33.73047,0 61.16797,-27.441408 61.16797,-61.16797 0,-33.726562 -27.44141,-61.164062 -61.16797,-61.164062 -33.726562,0 -61.167969,27.4375 -61.167969,61.164062 0,33.730469 27.441407,61.16797 61.167969,61.16797 z m 0,-92.332032 c 17.18359,0 31.16406,13.980468 31.16406,31.164062 0,17.183594 -13.98047,31.164062 -31.16406,31.164062 -17.183594,0 -31.164062,-13.980468 -31.164062,-31.164062 0,-17.183594 13.980468,-31.164062 31.164062,-31.164062 z m 0,0"/>
                        <path
                            d="M 207.74609,153.10937 H 25.257812 c -7.894531,0 -15.183593,3.58594 -20.003906,9.83985 -4.8125,6.25781 -6.417968,14.22265 -4.402344,21.85547 l 39.285157,148.59765 c 9.148437,34.6211 40.550781,58.80078 76.363281,58.80078 35.80859,0 67.21094,-24.17968 76.36328,-58.80078 l 39.28516,-148.59765 c 2.01953,-7.63282 0.41406,-15.59766 -4.40235,-21.85157 -4.8164,-6.25781 -12.10547,-9.84375 -20,-9.84375 z m -43.88672,172.6211 c -5.67578,21.47265 -25.15234,36.46875 -47.35937,36.46875 -22.207031,0 -41.683594,-14.9961 -47.359375,-36.46875 L 31.4375,183.10937 h 170.125 z m 0,0"/>
                    </g>
                </svg>
                <svg v-else-if="callData.lookingFor === 'f'"
                     fill="white"
                     viewBox="0 -60 512.00066 512"
                     xmlns="http://www.w3.org/2000/svg">
                    <defs id="defs14"/>
                    <g id="g833"
                       transform="matrix(1.3075052,0,0,1.3075052,-261.11916,-60.000334)">
                        <path
                            d="m 395.5,122.33203 c 33.73047,0 61.16797,-27.437499 61.16797,-61.164061 C 456.66797,27.4375 429.22656,0 395.5,0 c -33.72656,0 -61.16797,27.441406 -61.16797,61.167969 0,33.726562 27.44141,61.164061 61.16797,61.164061 z m 0,-92.328124 c 17.1875,0 31.16406,13.980469 31.16406,31.164063 0,17.183593 -13.98047,31.164062 -31.16406,31.164062 -17.18359,0 -31.16406,-13.980469 -31.16406,-31.164062 0,-17.183594 13.98047,-31.164063 31.16406,-31.164063 z m 0,0"/>
                        <path
                            d="M 511.14844,359.89062 471.86719,211.29687 c -9.15235,-34.625 -40.55469,-58.80078 -76.36719,-58.80078 -35.80859,0 -67.21094,24.17578 -76.36328,58.79688 l -39.28516,148.59765 c -2.01562,7.63282 -0.41015,15.59766 4.40625,21.85157 4.8125,6.25781 12.10547,9.84375 20,9.84375 h 182.48828 c 7.89453,0 15.1836,-3.58594 20,-9.83985 4.8125,-6.25781 6.42188,-14.22265 4.40235,-21.85547 z m -200.71094,1.69141 37.70312,-142.62109 c 5.67579,-21.46875 25.15235,-36.46485 47.35938,-36.46485 22.20703,0 41.68359,14.9961 47.35937,36.46485 l 37.70313,142.62109 z m 0,0"/>
                    </g>
                </svg>
                <svg v-else viewBox="0 -60 512.00066 512" xmlns="http://www.w3.org/2000/svg" fill="white">
                    <path
                        d="m116.5 122.949219c33.730469 0 61.167969-27.441407 61.167969-61.167969s-27.441407-61.164062-61.167969-61.164062-61.167969 27.4375-61.167969 61.164062c0 33.730469 27.441407 61.167969 61.167969 61.167969zm0-92.332031c17.183594 0 31.164062 13.980468 31.164062 31.164062s-13.980468 31.164062-31.164062 31.164062-31.164062-13.980468-31.164062-31.164062 13.980468-31.164062 31.164062-31.164062zm0 0"/>
                    <path
                        d="m207.746094 153.109375h-182.488282c-7.894531 0-15.183593 3.585937-20.003906 9.839844-4.8125 6.257812-6.417968 14.222656-4.402344 21.855469l39.285157 148.597656c9.148437 34.621094 40.550781 58.800781 76.363281 58.800781 35.808594 0 67.210938-24.179687 76.363281-58.800781l39.285157-148.597656c2.019531-7.632813.414062-15.597657-4.402344-21.851563-4.816406-6.257813-12.105469-9.84375-20-9.84375zm-43.886719 172.621094c-5.675781 21.472656-25.152344 36.46875-47.359375 36.46875s-41.683594-14.996094-47.359375-36.46875l-37.703125-142.621094h170.125zm0 0"/>
                    <path
                        d="m395.5 122.332031c33.730469 0 61.167969-27.4375 61.167969-61.164062 0-33.730469-27.441407-61.167969-61.167969-61.167969s-61.167969 27.441406-61.167969 61.167969c0 33.726562 27.441407 61.164062 61.167969 61.164062zm0-92.328125c17.1875 0 31.164062 13.980469 31.164062 31.164063 0 17.183593-13.980468 31.164062-31.164062 31.164062s-31.164062-13.980469-31.164062-31.164062c0-17.183594 13.980468-31.164063 31.164062-31.164063zm0 0"/>
                    <path
                        d="m511.148438 359.890625-39.28125-148.59375c-9.152344-34.625-40.554688-58.800781-76.367188-58.800781-35.808594 0-67.210938 24.175781-76.363281 58.796875l-39.285157 148.597656c-2.015624 7.632813-.410156 15.597656 4.40625 21.851563 4.8125 6.257812 12.105469 9.84375 20 9.84375h182.488282c7.894531 0 15.183594-3.585938 20-9.839844 4.8125-6.257813 6.421875-14.222656 4.402344-21.855469zm-200.710938 1.691406 37.703125-142.621093c5.675781-21.46875 25.152344-36.464844 47.359375-36.464844s41.683594 14.996094 47.359375 36.464844l37.703125 142.621093zm0 0"/>
                </svg>
            </button>
        </div>

        <div class="flex flex-row items-center justify-center py-4 space-x-12">
            <button v-if="callData.otherPerson" class="bg-red-500 h-16 w-16 rounded-full flex justify-center items-center
                  text-white shadow shadow-md shadow-gray-500 disabled:bg-gray-300"
                    disabled>
                <i class="ri-shut-down-line text-3xl align-middle"></i>
            </button>
            <Link v-else class="bg-red-500 h-16 w-16 rounded-full flex justify-center items-center
                  text-white shadow shadow-md shadow-gray-500 disabled:bg-gray-400"
                  :href="$route('checkout', person.username)">
                <i class="ri-shut-down-line text-3xl align-middle"></i>
            </Link>
            <button v-if="callData.otherPerson"
                    class="bg-red-500 h-16 w-16 rounded-full flex justify-center items-center
                    text-white shadow shadow-md shadow-gray-500"
                    @click="hangupCurrent">
                <i class="ri-close-circle-line text-3xl align-middle"></i>
            </button>
            <button v-else
                    class="bg-blue-500 h-16 w-16 rounded-full flex justify-center items-center
                    text-white shadow shadow-md shadow-gray-500"
                    @click="getAnOtherPerson">
                <i :class="{'animate-spin': refreshing}" class="ri-refresh-line text-3xl align-middle"></i>
            </button>
            <button @click="call"
                    class="bg-green-500 h-16 w-16 rounded-full text-white shadow shadow-md shadow-gray-500">
                <i class="ri-phone-line text-3xl align-middle"></i>
            </button>
        </div>

    </div>

</template>
