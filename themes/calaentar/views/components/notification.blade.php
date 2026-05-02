<div x-data>
    <template x-for="(notification, index) in $store.notifications.notifications" :key="notification.id">
        <div x-show="notification.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90" @click="$store.notifications.removeNotification(notification.id)"
            :class="notification.type === 'success' ? 'gradient-bg glow' : 'bg-red-500/80 shadow-[0_0_24px_-4px_rgba(220,38,38,0.6)]'"
            class="fixed text-white px-5 py-2.5 rounded-full shadow-2xl backdrop-blur-md mb-4 z-50 border border-white/10"
            :style="'top: ' + (20 + index * 60) + 'px;left: 50%; transform: translateX(-50%);'">
            <p x-text="notification.message"></p>
        </div>
    </template>
</div>
