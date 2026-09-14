<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="text-xl font-bold">Frases de pagos</h2>
    <p class="mt-2 text-sm text-gray-500">Administra los mensajes rápidos compartidos para pagos incompletos y anulados.</p>
    <button id="abrirFrasesPago" type="button" class="mt-4 rounded-xl bg-blue-600 px-4 py-3 font-bold text-white hover:bg-blue-700">Administrar frases</button>
</section>
<dialog id="modalFrasesPago" aria-labelledby="tituloFrasesPago" class="m-auto w-[calc(100%-2rem)] max-w-4xl max-h-[90dvh] rounded-2xl border-0 bg-white p-0 text-gray-800 shadow-2xl backdrop:bg-black/60 backdrop:backdrop-blur-sm">
    <div class="relative">
        <header class="flex items-start gap-4 border-b border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 sm:p-6">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600"><i class="fas fa-comment-dots text-2xl" aria-hidden="true"></i></span>
            <div class="min-w-0 flex-1">
                <p class="mb-1 text-xs font-bold uppercase tracking-wider text-blue-600">Mensajes rápidos</p>
                <h2 id="tituloFrasesPago" class="text-xl sm:text-2xl font-extrabold">Frases de pagos</h2>
                <p class="mt-1 text-sm text-gray-500">Personaliza los mensajes para revisar tus pagos.</p>
            </div>
            <button id="cerrarFrasesPago" type="button" aria-label="Cerrar" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-gray-400 shadow-sm transition-colors hover:bg-gray-100 hover:text-gray-700 focus:ring-2 focus:ring-blue-300"><i class="fas fa-xmark text-lg" aria-hidden="true"></i></button>
        </header>
        <div class="p-5 sm:p-6">
            <p id="avisoFrasesPago" role="status" aria-live="polite" class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800 empty:hidden"></p>
            <div class="grid items-start gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-1 font-bold text-gray-800">Tus frases guardadas</h3>
                    <p class="mb-4 text-xs text-gray-500">Selecciona Editar para modificar un mensaje.</p>
                    <div id="listaFrasesPago" class="space-y-4 md:max-h-[48dvh] md:overflow-y-auto md:pr-2"></div>
                </div>
                <form id="formFrasePago" class="rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5 space-y-4">
                    <div class="flex items-center gap-2 text-blue-700"><i class="fas fa-pen-to-square" aria-hidden="true"></i><h3 id="tituloFormFrase" class="font-bold">Agregar frase</h3></div>
                    <input type="hidden" name="csrf_token" value="<?php echo escaparPerfil($_SESSION['csrf_perfil']); ?>">
                    <input type="hidden" name="id" value="0">
                    <label class="block text-sm font-bold text-gray-600">Estado del pago
                        <select name="estado" class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:bg-gray-100">
                            <option value="incompleto">Incompleto</option><option value="anulado">Anulado</option>
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-gray-600">Mensaje
                        <textarea name="texto" required maxlength="255" rows="5" class="mt-2 block w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-3 font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Escribe la frase que aparecerá en los mensajes rápidos…"></textarea>
                    </label>
                    <p class="text-right text-xs text-gray-400">Máximo 255 caracteres</p>
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 font-bold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-50"><i class="fas fa-floppy-disk mr-2" aria-hidden="true"></i>Guardar frase</button>
                        <button id="nuevaFrasePago" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 font-bold text-gray-600 transition-colors hover:bg-gray-100"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Nueva frase</button>
                    </div>
                </form>
            </div>
        </div>
        <footer class="border-t border-gray-100 bg-gray-50 px-5 py-4 text-xs text-gray-500 sm:px-6"><i class="fas fa-circle-info mr-1.5 text-blue-500" aria-hidden="true"></i>Las frases se comparten en el módulo de pagos.</footer>
    </div>
</dialog>
<script src="../../Assets/js/frases_perfil.js"></script>
