<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Pengaturan Profil & Akun" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-arena-base text-volt font-display font-black text-2xl flex items-center justify-center shadow-volt-glow-sm -skew-x-3 shrink-0">
                        <span class="skew-x-3">
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display font-black text-2xl sm:text-3xl text-courtSlate-900 tracking-tight uppercase">
                                Pengaturan Profil & Akun
                            </h1>
                            <span
                                v-if="$page.props.auth.user.membership"
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 text-xs font-display font-black tracking-wider uppercase rounded -skew-x-6 border',
                                    $page.props.auth.user.membership.tier === 'gold'
                                        ? 'bg-amber-100 text-amber-900 border-amber-300'
                                        : $page.props.auth.user.membership.tier === 'silver'
                                        ? 'bg-slate-100 text-slate-700 border-slate-300'
                                        : 'bg-orange-50 text-orange-800 border-orange-200'
                                ]"
                            >
                                <span class="inline-block transform skew-x-6">
                                    Tier {{ $page.props.auth.user.membership.tier }}
                                </span>
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-courtSlate-600 mt-0.5">
                            Kelola identitas akun member, kata sandi keamanan, dan privasi Anda di Smash Arena.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('membership.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-display font-bold uppercase tracking-wider bg-courtSlate-100 hover:bg-courtSlate-200 text-courtSlate-800 border border-courtSlate-200 transition"
                    >
                        ⭐ Membership Saya
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Section 1: Profil Information -->
                <div class="relative overflow-hidden bg-white rounded-2xl border-2 border-courtSlate-200 p-6 sm:p-8 shadow-card-elevated court-stripe-accent">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-volt via-emerald-400 to-lime-500"></div>
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- Section 2: Update Password -->
                <div class="relative overflow-hidden bg-white rounded-2xl border-2 border-courtSlate-200 p-6 sm:p-8 shadow-card-elevated court-stripe-accent">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-cyan-400 via-volt to-emerald-400"></div>
                    <UpdatePasswordForm />
                </div>

                <!-- Section 3: Delete Account -->
                <div class="relative overflow-hidden bg-white rounded-2xl border-2 border-courtOrange/30 p-6 sm:p-8 shadow-card-elevated">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-courtOrange to-red-500"></div>
                    <DeleteUserForm />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
