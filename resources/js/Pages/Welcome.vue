<script setup lang="ts">
import {Head, Link, useForm} from '@inertiajs/inertia-vue3'
import route from 'ziggy-js'

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String
})

const form = useForm({
    username: '' as String,
    gender: 'm' as string
});

const submit = () => {
    form.post(route('start-chat'))
};


</script>

<template>
    <Head title="Welcome"/>

    <form @submit.prevent="submit" class="h-screen p-6 w-screen flex flex-col">
        <div class="grow flex items-center justify-center flex-col px-10 space-y-6">
            <input type="text" name="user-name" id="user-name"
                   autocomplete="given-name"
                   v-model="form.username"
                   placeholder="@username"
                   autofocus
                   required
                   class="max-w-lg block text-center w-full rounded-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md"/>
            <div v-show="form.errors.username">
                <p class="text-sm text-red-600">
                    {{ form.errors.username }}
                </p>
            </div>

            <select
                v-model="form.gender"
                required
                class="max-w-lg block rounded-full text-center w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:max-w-xs sm:text-sm border-gray-300 rounded-md">
                <option value="m">Male</option>
                <option value="f">Female</option>
            </select>

            <button type="submit" class=" max-w-lg bg-green-500 text-white block py-2 w-full rounded-full shadow-md">
                start
            </button>
        </div>

    </form>

</template>
