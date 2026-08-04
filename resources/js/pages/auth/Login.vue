<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { authStore } from '@/stores/auth';

const router = useRouter();
const form = ref({
    email: '',
    password: '',
    remember: false,
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    errors.value = {};
    processing.value = true;
    try {
        const res = await authStore.login(form.value);
        if (res?.two_factor_required) {
            router.push('/two-factor-challenge');
            return;
        }
        router.push('/dashboard');
    } catch (err: any) {
        if (err.response && err.response.status === 422) {
            const validationErrors = err.response.data.errors || {};
            for (const key in validationErrors) {
                errors.value[key] = validationErrors[key][0];
            }
        } else {
            errors.value.email = err.response?.data?.message || 'Login gagal. Periksa kembali email dan password.';
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <AuthBase title="Log in" description="Masukan email dan password Anda untuk masuk">
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" v-model="form.email" type="email" name="email" required autofocus :tabindex="1" autocomplete="email" placeholder="email@hutamakarya.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                    </div>
                    <PasswordInput id="password" v-model="form.password" name="password" required :tabindex="2" autocomplete="current-password" placeholder="Password" />
                    <InputError :message="errors.password" />
                </div>

                <Button type="submit" class="mt-4 w-full cursor-pointer bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white" :tabindex="4" :disabled="processing">
                    <Spinner v-if="processing" class="mr-2" />
                    Log in
                </Button>
            </div>
        </form>
    </AuthBase>
</template>
