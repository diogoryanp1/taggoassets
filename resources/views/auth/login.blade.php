<x-layouts.guest>
    <div class="grid w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-panel ring-1 ring-border lg:grid-cols-[1.05fr_0.95fr]">
        <section class="hidden bg-secondary p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <img src="{{ asset('branding/taggo-assets-horizontal-light.png') }}" alt="Taggo Assets" class="h-12 w-auto">
                <h1 class="mt-12 font-[Sora] text-3xl font-bold">Gestao patrimonial inteligente, segura e organizada.</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">Identifique, localize e controle ativos com isolamento por tenant, auditoria e permissoes por perfil.</p>
            </div>
            <p class="text-xs text-slate-400">Acesso exclusivo para usuarios convidados ou cadastrados pela administracao.</p>
        </section>
        <section class="p-6 sm:p-10">
            <div class="mb-8 lg:hidden">
                <img src="{{ asset('branding/taggo-assets-horizontal-dark.png') }}" alt="Taggo Assets" class="h-11 w-auto">
            </div>
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase text-primary">Taggo Assets</p>
                <h2 class="mt-2 font-[Sora] text-2xl font-bold text-text-primary">Acessar plataforma</h2>
                <p class="mt-2 text-sm text-text-secondary">Use suas credenciais administrativas ou o convite recebido.</p>
            </div>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <x-input name="email" label="E-mail" type="email" autocomplete="email" required autofocus />
                <x-input name="password" label="Senha" type="password" autocomplete="current-password" required />
                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm text-text-secondary"><input name="remember" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"> Lembrar-me</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:underline">Recuperar senha</a>
                </div>
                @if($errors->any())
                    <x-alert type="error">Nao foi possivel autenticar com as credenciais informadas.</x-alert>
                @endif
                <x-button class="w-full" x-bind:disabled="submitting"><span x-show="!submitting">Entrar</span><span x-show="submitting">Entrando...</span></x-button>
            </form>
        </section>
    </div>
</x-layouts.guest>
