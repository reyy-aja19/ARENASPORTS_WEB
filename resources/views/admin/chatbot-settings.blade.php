
@extends('layouts.admin')

@section('content')
<div class="panel-card chatbot-settings-card">
    <div class="panel-title">Konfigurasi Asisten AI</div>

    <p class="chatbot-description">
        Atur provider, model, dan instruksi asisten AI ArenaSports.
        API key harus disimpan pada file .env, bukan di database.
    </p>

    <form
        method="POST"
        action="{{ route('admin.chatbot.update') }}"
        class="chatbot-settings-form"
    >
        @csrf
        @method('PUT')

        <div class="chatbot-form-grid">
            <div class="chatbot-field">
                <label for="provider">Provider AI</label>
                <select id="provider" name="provider" required>
                    <option value="openai"
                        @selected(old('provider', $settings->provider) === 'openai')>
                        OpenAI
                    </option>
                    <option value="custom"
                        @selected(old('provider', $settings->provider) === 'custom')>
                        Custom endpoint
                    </option>
                </select>
            </div>

            <div class="chatbot-field">
                <label for="model">Model AI</label>
                <input
                    id="model"
                    type="text"
                    name="model"
                    value="{{ old('model', $settings->model) }}"
                    maxlength="100"
                    placeholder="Contoh: gpt-4o-mini"
                    required
                >
            </div>

            <div class="chatbot-field chatbot-field-full">
                <label for="system_prompt">System prompt</label>
                <textarea
                    id="system_prompt"
                    name="system_prompt"
                    rows="8"
                    placeholder="Tuliskan instruksi untuk asisten AI ArenaSports..."
                >{{ old('system_prompt', $settings->system_prompt) }}</textarea>
                <small>
                    Contoh: Anda adalah asisten ArenaSports yang membantu
                    pengguna mencari lapangan, memahami pemesanan, dan
                    menjawab pertanyaan seputar layanan.
                </small>
            </div>

            <div class="chatbot-toggle-row chatbot-field-full">
                <div>
                    <strong>Aktifkan chatbot AI</strong>
                    <p>Aktifkan setelah integrasi API backend selesai.</p>
                </div>

                <label class="chatbot-switch" for="is_enabled">
                    <input
                        id="is_enabled"
                        type="checkbox"
                        name="is_enabled"
                        value="1"
                        @checked((bool) old('is_enabled', $settings->is_enabled))
                    >
                    <span class="chatbot-switch-slider"></span>
                </label>
            </div>
        </div>

        <div class="chatbot-form-actions">
            <button type="submit" class="btn-ui btn-green">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<style>
.chatbot-settings-card {
    width: 100%;
    box-sizing: border-box;
    padding: 28px;
}
.chatbot-description {
    line-height: 1.7;
    color: #607568;
    margin: 12px 0 26px;
}
.chatbot-settings-form {
    width: 100%;
}
.chatbot-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}
.chatbot-field {
    display: flex;
    flex-direction: column;
    gap: 9px;
    min-width: 0;
}
.chatbot-field label {
    font-size: 14px;
    font-weight: 700;
    color: #175b32;
}
.chatbot-field input:not([type="checkbox"]),
.chatbot-field select,
.chatbot-field textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
    padding: 13px 15px;
    border: 1px solid #dce9df;
    border-radius: 12px;
    background: #fff;
    color: #183b28;
    font: inherit;
    outline: none;
}
.chatbot-field textarea {
    resize: vertical;
    min-height: 180px;
    line-height: 1.6;
}
.chatbot-field input:focus,
.chatbot-field select:focus,
.chatbot-field textarea:focus {
    border-color: #0b9846;
    box-shadow: 0 0 0 3px rgba(11, 152, 70, .10);
}
.chatbot-field small {
    color: #708477;
    line-height: 1.5;
}
.chatbot-field-full {
    grid-column: 1 / -1;
}
.chatbot-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px;
    border: 1px solid #e0ece3;
    border-radius: 14px;
    background: #f8fcf9;
}
.chatbot-toggle-row strong {
    color: #175b32;
}
.chatbot-toggle-row p {
    margin: 5px 0 0;
    font-size: 13px;
    color: #718477;
}
.chatbot-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 29px;
    flex-shrink: 0;
}
.chatbot-switch input {
    width: 0;
    height: 0;
    opacity: 0;
}
.chatbot-switch-slider {
    position: absolute;
    inset: 0;
    cursor: pointer;
    background: #cbd8ce;
    border-radius: 30px;
    transition: .2s;
}
.chatbot-switch-slider::before {
    content: "";
    position: absolute;
    width: 21px;
    height: 21px;
    left: 4px;
    top: 4px;
    background: white;
    border-radius: 50%;
    transition: .2s;
    box-shadow: 0 1px 4px #0002;
}
.chatbot-switch input:checked + .chatbot-switch-slider {
    background: #0b9846;
}
.chatbot-switch input:checked + .chatbot-switch-slider::before {
    transform: translateX(23px);
}
.chatbot-form-actions {
    margin-top: 24px;
}
.chatbot-form-actions button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
}
@media (max-width: 760px) {
    .chatbot-settings-card {
        padding: 18px;
    }
    .chatbot-form-grid {
        grid-template-columns: 1fr;
    }
    .chatbot-field-full {
        grid-column: auto;
    }
}
</style>
@endsection
