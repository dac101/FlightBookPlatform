<script setup>
import { ref } from 'vue';

const iataCode = ref('');
const file = ref(null);
const uploading = ref(false);
const result = ref(null);
const error = ref('');

function onFileChange(e) {
    file.value = e.target.files[0] ?? null;
    error.value = '';
    result.value = null;
}

async function submit() {
    error.value = '';
    result.value = null;

    if (!iataCode.value || !file.value) {
        error.value = 'Airport code and file are both required.';
        return;
    }

    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('iata_code', iataCode.value.toUpperCase());
        formData.append('file', file.value);

        const response = await fetch('/admin/api/flight-imports', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            const messages = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : data.message ?? 'Upload failed.';
            error.value = messages;
            return;
        }

        result.value = data;
        iataCode.value = '';
        file.value = null;
        document.getElementById('flight-import-file').value = '';
    } catch (e) {
        error.value = 'An unexpected error occurred. Please try again.';
    } finally {
        uploading.value = false;
    }
}
</script>

<template>
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-600">Data Import</p>
            <h2 class="mt-1 text-xl font-semibold text-slate-900">Upload flight data</h2>
            <p class="mt-1 text-sm text-slate-500">
                Upload an AviationStack JSON file for a given airport. The file will be saved to storage and dispatched to Horizon for processing.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="flight-import-iata">
                    Airport IATA code
                </label>
                <input
                    id="flight-import-iata"
                    v-model="iataCode"
                    type="text"
                    maxlength="3"
                    placeholder="e.g. JFK"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm uppercase text-slate-900 focus:border-violet-500 focus:ring-violet-500 sm:max-w-[180px]"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700" for="flight-import-file">
                    JSON file
                </label>
                <input
                    id="flight-import-file"
                    type="file"
                    accept=".json,application/json"
                    class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-violet-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-violet-700 hover:file:bg-violet-100"
                    @change="onFileChange"
                    required
                />
                <p class="mt-1 text-xs text-slate-400">Max 10 MB · JSON format only</p>
            </div>

            <div
                v-if="error"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ error }}
            </div>

            <div
                v-if="result"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                <p class="font-medium">{{ result.message }}</p>
                <p class="mt-1 font-mono text-xs text-emerald-600">{{ result.path }}</p>
            </div>

            <button
                type="submit"
                :disabled="uploading"
                class="rounded-full bg-violet-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-700 disabled:opacity-50"
            >
                {{ uploading ? 'Uploading…' : 'Upload & dispatch to Horizon' }}
            </button>
        </form>
    </div>
</template>
