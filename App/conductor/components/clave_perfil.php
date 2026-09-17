<section class="my-6 rounded-2xl bg-white border border-gray-200 p-5 shadow-sm">
    <h2 class="text-xl font-bold">Cambiar contraseña</h2>
    <p class="mt-2 text-sm text-gray-600">Tu usuario es tu cédula. Inicialmente, tu contraseña también es tu cédula. Puedes cambiarla cuando quieras.</p>
    <form id="formClavePerfil" class="mt-4 flex flex-col gap-3">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_perfil'], ENT_QUOTES); ?>">
        <label class="font-semibold">Contraseña actual
            <input name="actual" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-xl border border-gray-300 p-3">
        </label>
        <label class="font-semibold">Nueva contraseña
            <span class="relative mt-1 block">
                <input id="nuevaClavePerfil" name="nueva" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="w-full rounded-xl border border-gray-300 py-3 pl-3 pr-12">
                <button type="button" data-toggle-password="nuevaClavePerfil" aria-label="Mostrar nueva contraseña" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-gray-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </span>
        </label>
        <p class="text-sm text-gray-500">Usa al menos 8 caracteres.</p>
        <label class="font-semibold">Confirmar nueva contraseña
            <span class="relative mt-1 block">
                <input id="confirmarClavePerfil" name="confirmacion" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="w-full rounded-xl border border-gray-300 py-3 pl-3 pr-12">
                <button type="button" data-toggle-password="confirmarClavePerfil" aria-label="Mostrar confirmación de contraseña" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-gray-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </span>
        </label>
        <button type="submit" class="rounded-xl bg-blue-600 p-3 font-bold text-white disabled:opacity-50">Guardar contraseña</button>
        <p id="avisoClavePerfil" role="status" aria-live="polite"></p>
    </form>
</section>
<script src="../../Assets/js/perfil.js?v=<?php echo hash_file('sha256', __DIR__ . '/../../../Assets/js/perfil.js'); ?>"></script>
