<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    courts: {
        type: Array,
        required: true,
    },
    today: {
        type: String,
        required: true,
    },
    reverbConfig: {
        type: Object,
        required: true,
    },
});

const selectedCourtId = ref(props.courts[0]?.id || 1);
const selectedDate = ref(props.today);
const startTime = ref('09:00');
const endTime = ref('10:00');

const connectionState = ref('connecting');
const connectionError = ref(null);
const eventsLog = ref([]);
const isTriggering = ref(false);
const triggerFeedback = ref(null);

let activeChannel = null;

const setupEchoListener = () => {
    if (typeof window === 'undefined' || !window.Echo) {
        connectionState.value = 'echo_not_found';
        return;
    }

    // Monitor Pusher connection state
    const pusher = window.Echo.connector?.pusher;
    if (pusher?.connection) {
        connectionState.value = pusher.connection.state;

        pusher.connection.bind('state_change', (states) => {
            connectionState.value = states.current;
            if (states.current === 'connected') {
                connectionError.value = null;
            }
        });

        pusher.connection.bind('error', (err) => {
            connectionError.value = err?.error?.data?.message || err?.message || 'WebSocket Connection Error';
        });
    }

    // Leave old channel if any
    if (activeChannel) {
        window.Echo.leave(`court.${activeChannel}`);
    }

    activeChannel = selectedCourtId.value;
    const channelName = `court.${selectedCourtId.value}`;

    // Listen to both relative and absolute event names
    window.Echo.channel(channelName)
        .listen('.BookingCreated', (e) => handleIncomingEvent('BookingCreated', e))
        .listen('BookingCreated', (e) => handleIncomingEvent('BookingCreated', e))
        .listen('.BookingCancelled', (e) => handleIncomingEvent('BookingCancelled', e))
        .listen('BookingCancelled', (e) => handleIncomingEvent('BookingCancelled', e));
};

const handleIncomingEvent = (eventName, data) => {
    // Avoid immediate duplicate logs if both listeners fire
    const last = eventsLog.value[0];
    if (last && last.event === eventName && last.data.booking_id === data.booking_id && (Date.now() - last.timestampMs < 300)) {
        return;
    }

    eventsLog.value.unshift({
        id: Date.now() + Math.random(),
        event: eventName,
        data: data,
        time: new Date().toLocaleTimeString('id-ID'),
        timestampMs: Date.now(),
    });

    if (eventsLog.value.length > 50) {
        eventsLog.value.pop();
    }
};

watch(selectedCourtId, () => {
    setupEchoListener();
});

onMounted(() => {
    setupEchoListener();
});

onUnmounted(() => {
    if (activeChannel && window.Echo) {
        window.Echo.leave(`court.${activeChannel}`);
    }
});

const triggerBroadcast = async (type) => {
    isTriggering.value = true;
    triggerFeedback.value = null;

    try {
        const res = await window.axios.post(route('debug.broadcast.trigger'), {
            court_id: selectedCourtId.value,
            type: type,
            booking_date: selectedDate.value,
            start_time: startTime.value,
            end_time: endTime.value,
        });

        triggerFeedback.value = {
            status: 'success',
            message: `Event ${res.data.event} terkirim ke channel ${res.data.channel} pada ${new Date(res.data.dispatched_at).toLocaleTimeString('id-ID')}`,
        };
    } catch (err) {
        triggerFeedback.value = {
            status: 'error',
            message: err.response?.data?.message || err.message || 'Gagal mengirim event broadcast',
        };
    } finally {
        isTriggering.value = false;
    }
};

const clearLogs = () => {
    eventsLog.value = [];
};
</script>

<template>
    <Head title="WebSocket Real-Time Broadcast Tester (Debug Local)" />

    <div class="min-h-screen bg-arena-base text-courtSlate-100 p-6 md:p-12 font-sans selection:bg-volt selection:text-arena-base">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-courtSlate-800 pb-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-courtOrange/20 border border-courtOrange/40 text-courtOrange text-xs font-mono font-bold uppercase tracking-wider mb-2">
                        <span>🛠️ DEV ENVIRONMENT ONLY</span>
                    </div>
                    <h1 class="text-3xl font-display font-black tracking-wider uppercase text-white">
                        WebSocket Broadcast Tester
                    </h1>
                    <p class="text-sm text-courtSlate-400 mt-1">
                        Tool visual untuk verifikasi konektivitas Laravel Reverb & Echo secara real-time tanpa membuat booking sungguhan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('bookings.create')"
                        target="_blank"
                        class="px-4 py-2 bg-courtSlate-800 hover:bg-courtSlate-700 text-white rounded-xl text-xs font-display font-bold uppercase tracking-wider transition border border-courtSlate-700 flex items-center gap-1.5"
                    >
                        <span>Buka Halaman Booking ↗</span>
                    </Link>
                </div>
            </div>

            <!-- Server & Connection Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- WebSocket Status -->
                <div class="bg-arena-surface border border-courtSlate-800 rounded-2xl p-5 space-y-2">
                    <div class="text-xs font-mono uppercase tracking-wider text-courtSlate-400">WebSocket Status</div>
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-3.5 h-3.5 rounded-full animate-pulse"
                            :class="{
                                'bg-volt shadow-[0_0_12px_#CCFF00]': connectionState === 'connected',
                                'bg-courtOrange shadow-[0_0_12px_#FF5500]': connectionState === 'connecting',
                                'bg-red-500 shadow-[0_0_12px_#EF4444]': connectionState === 'unavailable' || connectionState === 'failed' || connectionState === 'disconnected',
                                'bg-gray-500': connectionState === 'echo_not_found',
                            }"
                        ></span>
                        <span class="text-lg font-display font-black tracking-wide uppercase text-white">
                            {{ connectionState }}
                        </span>
                    </div>
                    <p v-if="connectionError" class="text-xs text-red-400 mt-1">
                        {{ connectionError }}
                    </p>
                    <p v-else-if="connectionState === 'connected'" class="text-xs text-courtSlate-400">
                        Klien terhubung ke Laravel Reverb server.
                    </p>
                    <p v-else class="text-xs text-courtOrange">
                        Jalankan <code class="bg-courtSlate-900 px-1 py-0.5 rounded text-volt font-mono">php artisan reverb:start</code> jika belum aktif.
                    </p>
                </div>

                <!-- Active Channel -->
                <div class="bg-arena-surface border border-courtSlate-800 rounded-2xl p-5 space-y-2">
                    <div class="text-xs font-mono uppercase tracking-wider text-courtSlate-400">Listening Channel</div>
                    <div class="text-lg font-mono font-bold text-volt">
                        court.{{ selectedCourtId }}
                    </div>
                    <p class="text-xs text-courtSlate-400">
                        Event: <span class="text-white font-mono">BookingCreated, BookingCancelled</span>
                    </p>
                </div>

                <!-- Reverb Config -->
                <div class="bg-arena-surface border border-courtSlate-800 rounded-2xl p-5 space-y-1 text-xs font-mono">
                    <div class="text-xs font-mono uppercase tracking-wider text-courtSlate-400 mb-1">Reverb Configuration</div>
                    <div class="text-courtSlate-300">Driver: <span class="text-white">{{ reverbConfig.broadcast_driver }}</span></div>
                    <div class="text-courtSlate-300">Host: <span class="text-white">{{ reverbConfig.host }}:{{ reverbConfig.port }}</span></div>
                    <div class="text-courtSlate-300">Scheme: <span class="text-white">{{ reverbConfig.scheme }}</span></div>
                    <div class="text-courtSlate-400 truncate">App Key: <span class="text-courtSlate-300">{{ reverbConfig.app_key }}</span></div>
                </div>
            </div>

            <!-- Trigger Control Panel -->
            <div class="bg-arena-surface border border-courtSlate-800 rounded-2xl p-6 space-y-6">
                <div class="border-b border-courtSlate-800 pb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-display font-black uppercase text-white tracking-wide">
                            Broadcast Simulator Controller
                        </h2>
                        <p class="text-xs text-courtSlate-400">
                            Pilih parameter lalu klik tombol di bawah untuk men-dispatch event dari backend melalui Laravel Reverb.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Court Select -->
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-courtSlate-300 tracking-wider mb-2">
                            Pilih Lapangan
                        </label>
                        <select
                            v-model="selectedCourtId"
                            class="w-full bg-courtSlate-900 border border-courtSlate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-volt focus:ring-1 focus:ring-volt font-medium"
                        >
                            <option v-for="court in courts" :key="court.id" :value="court.id">
                                {{ court.name }} (ID: {{ court.id }})
                            </option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-courtSlate-300 tracking-wider mb-2">
                            Tanggal Booking
                        </label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="w-full bg-courtSlate-900 border border-courtSlate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-volt focus:ring-1 focus:ring-volt font-medium"
                        />
                    </div>

                    <!-- Start Time -->
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-courtSlate-300 tracking-wider mb-2">
                            Mulai (HH:mm)
                        </label>
                        <input
                            v-model="startTime"
                            type="text"
                            placeholder="09:00"
                            class="w-full bg-courtSlate-900 border border-courtSlate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-volt focus:ring-1 focus:ring-volt font-mono font-medium"
                        />
                    </div>

                    <!-- End Time -->
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-courtSlate-300 tracking-wider mb-2">
                            Selesai (HH:mm)
                        </label>
                        <input
                            v-model="endTime"
                            type="text"
                            placeholder="10:00"
                            class="w-full bg-courtSlate-900 border border-courtSlate-700 text-white rounded-xl px-3 py-2 text-sm focus:border-volt focus:ring-1 focus:ring-volt font-mono font-medium"
                        />
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <button
                        type="button"
                        :disabled="isTriggering"
                        @click="triggerBroadcast('created')"
                        class="px-5 py-3 rounded-xl bg-volt text-arena-base font-display font-black text-xs uppercase tracking-wider hover:bg-lime-400 hover:shadow-[0_0_15px_rgba(204,255,0,0.4)] transition disabled:opacity-50 flex items-center gap-2 -skew-x-3 cursor-pointer"
                    >
                        <span class="skew-x-3">⚡ Trigger Event: BookingCreated</span>
                    </button>

                    <button
                        type="button"
                        :disabled="isTriggering"
                        @click="triggerBroadcast('cancelled')"
                        class="px-5 py-3 rounded-xl bg-courtOrange text-white font-display font-black text-xs uppercase tracking-wider hover:bg-orange-600 hover:shadow-[0_0_15px_rgba(255,85,0,0.4)] transition disabled:opacity-50 flex items-center gap-2 -skew-x-3 cursor-pointer"
                    >
                        <span class="skew-x-3">🔄 Trigger Event: BookingCancelled</span>
                    </button>

                    <span v-if="isTriggering" class="text-xs text-courtSlate-400 animate-pulse font-mono">
                        Memproses broadcast dari backend...
                    </span>
                </div>

                <!-- Feedback Banner -->
                <div
                    v-if="triggerFeedback"
                    class="p-4 rounded-xl text-xs font-mono border"
                    :class="triggerFeedback.status === 'success' ? 'bg-volt/10 border-volt/30 text-volt' : 'bg-red-500/10 border-red-500/30 text-red-400'"
                >
                    {{ triggerFeedback.message }}
                </div>
            </div>

            <!-- Live WebSocket Packet Monitor Log -->
            <div class="bg-arena-surface border border-courtSlate-800 rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-courtSlate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-volt animate-ping"></span>
                        <h3 class="text-sm font-display font-black uppercase text-white tracking-wider">
                            Live WebSocket Incoming Event Stream (Received by Echo)
                        </h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-courtSlate-400 font-mono">{{ eventsLog.length }} event diterima</span>
                        <button
                            v-if="eventsLog.length > 0"
                            @click="clearLogs"
                            class="text-xs text-courtSlate-400 hover:text-white underline font-mono cursor-pointer"
                        >
                            Bersihkan Log
                        </button>
                    </div>
                </div>

                <div v-if="eventsLog.length === 0" class="py-12 text-center text-courtSlate-500 text-xs font-mono space-y-2">
                    <div class="text-2xl">📡</div>
                    <div>Belum ada event WebSocket yang tertangkap di browser ini.</div>
                    <div class="text-courtSlate-600">
                        Klik tombol trigger di atas atau lakukan booking/pembatalan di tab lain untuk melihat paket event masuk secara real-time.
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="item in eventsLog"
                        :key="item.id"
                        class="bg-courtSlate-900/90 border border-courtSlate-800 rounded-xl p-4 transition-all duration-300 hover:border-courtSlate-700"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider"
                                    :class="item.event === 'BookingCreated' ? 'bg-volt/20 text-volt border border-volt/30' : 'bg-courtOrange/20 text-courtOrange border border-courtOrange/30'"
                                >
                                    {{ item.event }}
                                </span>
                                <span class="text-xs text-courtSlate-300 font-mono">
                                    court.{{ item.data.court_id }}
                                </span>
                            </div>
                            <span class="text-xs text-courtSlate-500 font-mono">{{ item.time }}</span>
                        </div>

                        <!-- Slot details -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-mono text-courtSlate-300 bg-courtSlate-950/60 rounded-lg p-2.5 mb-2">
                            <div>Tanggal: <span class="text-white">{{ item.data.booking_date }}</span></div>
                            <div>Slot: <span class="text-white">{{ item.data.start_time }} - {{ item.data.end_time }}</span></div>
                            <div>Status: <span class="text-white uppercase">{{ item.data.status }}</span></div>
                            <div>User ID: <span class="text-white">{{ item.data.user_id }}</span></div>
                        </div>

                        <!-- Raw JSON payload collapse -->
                        <details class="text-[11px] font-mono text-courtSlate-500 cursor-pointer">
                            <summary class="hover:text-courtSlate-300">Raw JSON Payload</summary>
                            <pre class="mt-1 p-2 bg-black/40 rounded text-courtSlate-400 overflow-x-auto text-[10px]">{{ JSON.stringify(item.data, null, 2) }}</pre>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

