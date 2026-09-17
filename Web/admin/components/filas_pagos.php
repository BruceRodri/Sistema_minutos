<?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-receipt text-3xl mb-3 text-gray-300"></i>
                                    <p>No hay pagos que coincidan con los filtros seleccionados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p):
                                $esEspera = $p['estado'] === 'en_espera';
                                $esAnulado = $p['estado'] === 'anulado';
                                $esAprobado = $p['estado'] === 'aprobado';
                                $esIncompleto = $p['estado'] === 'incompleto';
                                $conductor = trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''));
                                $comprobantes = array_values(array_filter(array_map('strval', $p['comprobantes'] ?? [])));
                                $tieneComprobante = !empty($comprobantes) && !$esAnulado;
                                $etiquetaComprobante = function ($indice) use ($p) {
                                    return htmlspecialchars('../../Controllers/ComprobanteController.php?pago_id=' . (int)$p['id'] . '&archivo=' . rawurlencode($indice), ENT_QUOTES, 'UTF-8');
                                };
                            ?>
                            <tr id="pago-<?php echo (int)$p['id']; ?>" class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($conductor); ?></p>
                                    <p class="text-xs text-gray-500 font-mono">Código: <?php echo htmlspecialchars($p['codigo_conductor'] ?: '—'); ?></p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php foreach ($p['discos'] as $disco): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800 border border-blue-200 mr-1">
                                            <?php echo htmlspecialchars($disco); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php foreach ($p['fechas'] as $fecha): ?>
                                        <p class="text-sm text-gray-700 whitespace-nowrap">
                                            <i class="far fa-calendar mr-1 text-blue-400"></i><?php echo date('d/m/Y', strtotime($fecha)); ?>
                                        </p>
                                    <?php endforeach; ?>
                                    <?php if (!$p['fechas']): ?>
                                        <p class="text-sm text-gray-400">—</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 max-w-60 text-left">
                                    <?php $rutas = $p['rutas'] ?: ['—']; ?>
                                    <?php foreach ($rutas as $ruta): ?>
                                        <p class="text-sm text-gray-600 truncate"><i class="fas fa-route mr-1 text-indigo-400"></i><?php echo htmlspecialchars($ruta); ?></p>
                                    <?php endforeach; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($p['valores_individuales']): ?>
                                        <?php foreach ($p['valores_individuales'] as $item): ?>
                                            <p class="text-sm text-gray-700 whitespace-nowrap">
                                                <span class="text-xs text-gray-400 font-mono"><?php echo htmlspecialchars($item['disco']); ?></span>
                                                <span class="ml-2 font-semibold">$ <?php echo number_format((float)$item['valor'], 2, '.', ','); ?></span>
                                            </p>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="text-base font-extrabold text-gray-800">$ <?php echo number_format((float)$p['monto_total'], 2, '.', ','); ?></span>
                                    <?php if (($p['reporte_diferencia']['estado'] ?? '') === 'confirmado'): ?>
                                        <div class="mt-2 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">
                                            <p><i class="fas fa-wallet mr-1" aria-hidden="true"></i>Este pago generó saldo a favor</p>
                                            <p class="mt-1">Excedente registrado: $ <?php echo number_format($p['reporte_diferencia']['excedente_generado'], 2, '.', ','); ?></p>
                                            <p>Saldo disponible: $ <?php echo number_format($p['reporte_diferencia']['saldo_favor'], 2, '.', ','); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['usos_saldo'])): ?>
                                        <div class="mx-auto mt-2 w-64 max-w-full whitespace-normal rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white px-3 py-3 text-left shadow-sm">
                                            <p class="text-xs font-extrabold text-blue-800"><i class="fas fa-circle-check mr-1" aria-hidden="true"></i>Pagado con saldo a favor</p>
                                            <p class="mt-1 text-sm font-extrabold text-blue-900">Saldo utilizado: $ <?php echo number_format(array_sum(array_column($p['usos_saldo'], 'monto')), 2, '.', ','); ?></p>
                                            <div class="mt-2 space-y-2 border-t border-blue-100 pt-2">
                                                <?php foreach ($p['usos_saldo'] as $origenSaldo):
                                                    $numeroOrigen = trim((string)($origenSaldo['nro_comprobante'] ?? ''));
                                                    $numerosOrigen = json_decode($numeroOrigen, true);
                                                    if (is_array($numerosOrigen)) $numeroOrigen = implode(' · ', array_map('strval', $numerosOrigen));
                                                    $detalleOrigenSaldo = json_decode($origenSaldo['detalle_pagos'] ?? '', true);
                                                    $fechasOrigenSaldo = is_array($detalleOrigenSaldo)
                                                        ? array_values(array_unique(array_filter(array_map(static fn($fila) => $fila['fecha'] ?? null, $detalleOrigenSaldo))))
                                                        : [];
                                                    sort($fechasOrigenSaldo);
                                                    $fechaOrigen = implode(' · ', array_map(static fn($fecha) => date('d/m/Y', strtotime($fecha)), $fechasOrigenSaldo));
                                                    if ($fechaOrigen === '' && !empty($origenSaldo['fecha_pago'])) {
                                                        $fechaOrigen = date('d/m/Y', strtotime($origenSaldo['fecha_pago']));
                                                    }
                                                ?>
                                                    <div class="text-xs leading-relaxed text-blue-800">
                                                        <p class="font-bold"><?php echo (int)$origenSaldo['origen'] === (int)$p['id'] ? 'Origen: excedente de este mismo pago' : 'Origen del saldo'; ?></p>
                                                        <?php if ($numeroOrigen !== ''): ?>
                                                            <p class="break-words">Comprobante: <span class="font-semibold"><?php echo htmlspecialchars($numeroOrigen, ENT_QUOTES, 'UTF-8'); ?></span></p>
                                                        <?php else: ?>
                                                            <p class="text-blue-600">Sin número de comprobante registrado</p>
                                                        <?php endif; ?>
                                                        <?php if ($fechaOrigen !== ''): ?><p>Fecha pagada: <?php echo htmlspecialchars($fechaOrigen, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                                                        <?php if (count($p['usos_saldo']) > 1): ?><p class="font-semibold">Aporte de este origen: $ <?php echo number_format($origenSaldo['monto'], 2, '.', ','); ?></p><?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 min-w-72">
                                    <div class="flex flex-col gap-1.5 mt-2 <?php echo $esAnulado ? 'opacity-50 pointer-events-none' : ''; ?>"
                                         data-pago="<?php echo (int)$p['id']; ?>"
                                         data-codigos="<?php echo htmlspecialchars($p['nro_comprobante'] ?? ''); ?>"
                                         data-cantidad-comprobantes="<?php echo count($comprobantes); ?>"
                                         <?php echo $esAnulado ? 'data-bloqueado="1"' : ''; ?>>
                                        <div class="contenedorCodigos flex flex-col gap-1.5"></div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" data-agregar-codigo title="Agregar otro código"
                                                    <?php echo $esAnulado ? 'disabled' : ''; ?>
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button type="button" data-guardar-codigos
                                                    <?php echo $esAnulado ? 'disabled' : ''; ?>
                                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-bold text-white shadow hover:bg-blue-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                                                <i class="fas fa-floppy-disk mr-1.5"></i>Guardar
                                            </button>
                                            <span data-estado-codigos class="text-xs font-bold"></span>
                                        </div>
                                    </div>
                                    <?php if ($esAnulado): ?>
                                        <p class="mt-1 text-xs font-bold text-red-600"><i class="fas fa-lock mr-1"></i>Anulado: comprobante bloqueado</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($esAnulado): ?>
                                        <span class="text-xs font-bold text-gray-400">Comprobante no disponible</span>
                                    <?php elseif ($tieneComprobante): ?>
                                        <?php $cantidad = count($comprobantes); ?>
                                        <div class="flex flex-col gap-1.5">
                                            <?php foreach ($comprobantes as $i => $archivoRelativo): ?>
                                                <a href="<?php echo $etiquetaComprobante(basename($archivoRelativo)); ?>" target="_blank" rel="noopener"
                                                   title="Abrir comprobante en otra pestaña"
                                                   class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                                                    <i class="fas fa-eye mr-1.5"></i><?php echo !empty($p['comprobantes_saldo']) ? 'Comprobante de la diferencia' : 'Ver comprobante'; ?><?php echo $cantidad > 1 ? ' ' . ($i + 1) : ''; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs font-bold text-gray-400">Sin comprobante</span>
                                    <?php endif; ?>
                                    <?php if (!$esAnulado && !empty($p['comprobantes_saldo'])): ?>
                                        <div class="mt-3 flex flex-col gap-2 border-t border-gray-200 pt-3">
                                            <p class="text-xs font-bold text-emerald-700">Origen del saldo a favor</p>
                                            <?php foreach ($p['comprobantes_saldo'] as $indiceSaldo => $respaldo):
                                                $urlSaldo = '../../Controllers/ComprobanteController.php?pago_id=' . (int)$respaldo['pago_id'] . '&archivo=' . rawurlencode($respaldo['archivo']); ?>
                                                <a href="<?php echo htmlspecialchars($urlSaldo, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100">
                                                    <i class="fas fa-receipt mr-2"></i>Comprobante del saldo<?php echo count($p['comprobantes_saldo']) > 1 ? ' ' . ($indiceSaldo + 1) : ''; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($esAprobado): ?>
                                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700">
                                            <i class="fas fa-circle-check mr-1"></i>Aprobado
                                        </span>
                                    <?php elseif ($esAnulado): ?>
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700">
                                            <i class="fas fa-circle-xmark mr-1"></i>Anulado
                                        </span>
                                    <?php elseif ($esIncompleto): ?>
                                        <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-2.5 py-0.5 text-xs font-bold text-orange-700">
                                            <i class="fas fa-exclamation-circle mr-1"></i>Incompleto
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                            <i class="fas fa-clock mr-1"></i>En espera
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <button type="button" data-menu-acciones aria-haspopup="menu" aria-expanded="false" class="flex h-11 w-48 items-center justify-between gap-3 rounded-xl border border-indigo-600 bg-indigo-600 px-4 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                                        <span><i class="fas fa-sliders mr-2" aria-hidden="true"></i>Acciones</span><i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
                                    </button>
                                    <template data-opciones-acciones>
                                        <?php
                                        $opcionesAccion = [];
                                        if (!$esAnulado) {
                                            if (!$esAprobado) $opcionesAccion[] = ['aprobar','Aprobar','fa-check','bg-green-50 text-green-800 hover:bg-green-100 focus:bg-green-100'];
                                            if (!$esIncompleto) $opcionesAccion[] = ['incompleto','Incompleto','fa-triangle-exclamation','bg-orange-50 text-orange-800 hover:bg-orange-100 focus:bg-orange-100'];
                                            $opcionesAccion[] = ['desaprobar','Anular','fa-xmark','bg-red-50 text-red-800 hover:bg-red-100 focus:bg-red-100'];
                                            if (!$esEspera) $opcionesAccion[] = ['espera','En espera','fa-clock','bg-amber-50 text-amber-800 hover:bg-amber-100 focus:bg-amber-100'];
                                            if (empty($p['reporte_diferencia']) && in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true)) $opcionesAccion[] = ['registrar-excedente','Registrar excedente','fa-wallet','bg-teal-50 text-teal-800 hover:bg-teal-100 focus:bg-teal-100'];
                                        }
                                        $opcionesAccion[] = ['detalle','Ver detalle del saldo','fa-receipt','bg-violet-50 text-violet-800 hover:bg-violet-100 focus:bg-violet-100'];
                                        foreach ($opcionesAccion as [$accionMenu,$textoMenu,$iconoMenu,$colorMenu]): ?>
                                            <button type="button" role="menuitem" data-ejecutar-accion="<?php echo $accionMenu; ?>" class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-3 text-left text-sm font-bold outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-400 <?php echo $colorMenu; ?>"><i class="fas <?php echo $iconoMenu; ?> w-5 text-center" aria-hidden="true"></i><span><?php echo $textoMenu; ?></span></button>
                                        <?php endforeach; ?>
                                    </template>
                                    
                                    <dialog class="acciones-pago w-[94vw] max-w-xl max-h-[88vh] overflow-y-auto rounded-3xl border border-gray-100 bg-white p-5 text-left shadow-2xl backdrop:bg-slate-950/60 sm:p-7">
                                        <div class="mb-5 flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-xl text-teal-700"><i class="fas fa-wallet"></i></span>
                                                <div><h3 class="text-xl font-extrabold text-gray-800">Detalle del saldo</h3><p class="text-sm text-gray-500">Origen y uso del saldo a favor</p></div>
                                            </div>
                                            <button type="button" data-cerrar-acciones class="h-10 w-10 shrink-0 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" aria-label="Cerrar detalle">✕</button>
                                        </div>
                                        <div class="flex flex-col gap-4">
                                        <?php if (!empty($p['reporte_diferencia'])):
                                            $reporte = $p['reporte_diferencia'];
                                            $confirmado = $reporte['estado'] === 'confirmado'; ?>
                                            <section class="rounded-2xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-4 sm:p-5" data-panel-diferencia="<?php echo (int)$p['id']; ?>">
                                                <p class="text-xs font-extrabold uppercase tracking-wide text-teal-700"><?php echo $confirmado ? 'Excedente registrado' : 'Revisión de diferencia'; ?></p>
                                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    <div><p class="text-sm text-gray-600">Saldo generado</p><p class="text-2xl font-extrabold text-teal-800">$ <?php echo number_format($confirmado ? $reporte['excedente_generado'] : 0, 2); ?></p></div>
                                                    <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-sm font-semibold text-gray-600">Saldo disponible</p><p class="text-2xl font-extrabold text-teal-800">$ <?php echo number_format($confirmado ? $reporte['saldo_favor'] : 0, 2); ?></p></div>
                                                </div>
                                                <p class="mt-3 text-sm text-gray-600">Depósito registrado: <strong>$ <?php echo number_format($reporte['monto_depositado'], 2); ?></strong></p>
                                                <div class="mt-4 rounded-xl border border-teal-100 bg-white p-3">
                                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Nota de secretaría</p>
                                                    <p class="mt-1 whitespace-pre-wrap break-words text-sm text-gray-700"><?php echo htmlspecialchars($reporte['nota_admin'] ?: 'Sin nota registrada.', ENT_QUOTES, 'UTF-8'); ?></p>
                                                </div>
                                                <p data-estado-diferencia role="status" class="mt-3 text-sm font-bold text-teal-700"><i class="fas fa-circle-info mr-1"></i><?php echo $confirmado ? ((float)$reporte['saldo_favor'] > 0 ? 'Saldo confirmado y disponible' : 'Saldo utilizado por completo') : ($reporte['estado'] === 'pendiente' ? 'Pendiente de revisión' : 'Revisión sin saldo a favor'); ?></p>
                                                <?php if (empty($p['destinos_saldo']) && !$esAnulado && in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true)): ?>
                                                    <details class="mt-4 border-t border-teal-200 pt-3">
                                                        <summary class="cursor-pointer text-sm font-bold text-teal-800"><i class="fas fa-pen mr-2"></i>Editar revisión</summary>
                                                        <label class="mt-3 block text-sm font-bold text-gray-700">Saldo a favor confirmado
                                                            <input data-saldo-diferencia type="number" min="0" step="0.01" value="<?php echo number_format($reporte['saldo_favor'], 2, '.', ''); ?>" class="mt-1 w-full rounded-xl border border-gray-300 bg-white p-3">
                                                        </label>
                                                        <label class="mt-3 block text-sm font-bold text-gray-700">Nota para el usuario
                                                            <textarea data-nota-diferencia maxlength="255" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white p-3"><?php echo htmlspecialchars($reporte['nota_admin'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                        </label>
                                                        <button type="button" data-resolver-diferencia="<?php echo (int)$p['id']; ?>" class="mt-3 w-full rounded-xl bg-teal-600 px-4 py-3 font-bold text-white hover:bg-teal-700 disabled:opacity-50">Guardar revisión</button>
                                                    </details>
                                                <?php endif; ?>
                                            </section>
                                        <?php endif; ?>

                                        <?php foreach (['usos_saldo' => 'Origen del saldo utilizado', 'destinos_saldo' => 'Dónde se utilizó este saldo'] as $grupoSaldo => $tituloSaldo):
                                            if (empty($p[$grupoSaldo])) continue; ?>
                                            <section class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                                <h4 class="mb-3 text-sm font-extrabold text-gray-700"><?php echo $tituloSaldo; ?></h4>
                                                <div class="space-y-3">
                                                <?php foreach ($p[$grupoSaldo] as $movimiento):
                                                    $idRelacionado = (int)($grupoSaldo === 'usos_saldo' ? $movimiento['origen'] : $movimiento['pago_id']);
                                                    $mismoPago = $idRelacionado === (int)$p['id'];
                                                    $detalleRelacionado = json_decode($movimiento['detalle_pagos'] ?? '', true);
                                                    $discosRelacionados = is_array($detalleRelacionado) ? array_values(array_unique(array_filter(array_column($detalleRelacionado, 'disco')))) : [];
                                                    ?>
                                                    <article class="rounded-xl border border-gray-100 bg-white p-3 shadow-sm">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <p class="text-sm font-bold text-gray-800"><?php echo $mismoPago ? 'Aplicado en este mismo pago' : ($grupoSaldo === 'usos_saldo' ? 'Pago que generó el saldo' : 'Pago realizado con este saldo'); ?></p>
                                                            <span class="rounded-lg bg-blue-50 px-3 py-1 text-base font-extrabold text-blue-800">$ <?php echo number_format($movimiento['monto'], 2); ?></span>
                                                        </div>
                                                        <p class="mt-2 text-sm text-gray-500"><i class="far fa-calendar mr-1"></i>Fecha del pago: <?php echo date('d/m/Y', strtotime($movimiento['fecha_pago'])); ?></p>
                                                        <?php if ($discosRelacionados): ?><p class="mt-1 break-words text-sm text-gray-600">Disco: <?php echo htmlspecialchars(implode(' · ', $discosRelacionados), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                                                        <?php if (!$mismoPago): ?>
                                                            <a href="pagos.php?ver_pago=<?php echo $idRelacionado; ?>#pago-<?php echo $idRelacionado; ?>" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-blue-700 hover:underline"><i class="fas fa-arrow-up-right-from-square"></i>Ver pago</a>
                                                        <?php endif; ?>
                                                    </article>
                                                <?php endforeach; ?>
                                                </div>
                                            </section>
                                        <?php endforeach; ?>
                                        <?php if (empty($p['reporte_diferencia']) && empty($p['usos_saldo']) && empty($p['destinos_saldo'])): ?>
                                            <p class="rounded-2xl bg-gray-50 p-5 text-center text-gray-500">Este pago no tiene movimientos de saldo a favor.</p>
                                        <?php endif; ?>
                                        <?php if (!$esAnulado && empty($p['reporte_diferencia']) && in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true)): ?>
                                            <button type="button" data-registrar-excedente="<?php echo (int)$p['id']; ?>" hidden style="display:none">Registrar excedente</button>
                                        <?php endif; ?>
                                        <?php if ($esEspera): ?>
                                            <div class="flex gap-2 flex-wrap">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1.5"></i>Aprobar
                                                </button>
                                                <button type="button" data-incompleto="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-orange-500 transition-colors">
                                                    <i class="fas fa-triangle-exclamation mr-1.5"></i>Incompleto
                                                </button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-xmark mr-1.5"></i>Anular
                                                </button>
                                            </div>
                                        <?php elseif ($esIncompleto): ?>
                                            <div class="flex gap-2 flex-wrap">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1.5"></i>Aprobar
                                                </button>
                                                <button type="button" data-espera="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-200 transition-colors">
                                                    <i class="fas fa-clock mr-1.5"></i>En espera
                                                </button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none"
                                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-xmark mr-1.5"></i>Anular
                                                </button>
                                            </div>
                                            <div class="rounded-lg bg-orange-50 border border-orange-200 px-3 py-2 max-w-72 mt-2">
                                                <p class="text-xs font-bold text-orange-700"><i class="fas fa-triangle-exclamation mr-1"></i>Pago incompleto:</p>
                                                <p class="text-xs text-orange-600 break-words"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></p>
                                            </div>
                                        <?php elseif ($esAprobado): ?>
                                            <button type="button" data-editar-estado hidden style="display:none"
                                                    class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50 transition-colors">
                                                <i class="fas fa-pen mr-1"></i>Editar estado
                                            </button>
                                            <div data-opciones-estado class="hidden flex flex-wrap gap-2">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none" class="rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white">Aprobar</button>
                                                <button type="button" data-incompleto="<?php echo (int)$p['id']; ?>" hidden style="display:none" class="rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-white">Incompleto</button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>" hidden style="display:none" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">Anular</button>
                                                <button type="button" data-espera="<?php echo (int)$p['id']; ?>" hidden style="display:none" class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-bold text-amber-800">En espera</button>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" data-editar-estado hidden style="display:none" disabled
                                                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-400 cursor-not-allowed">
                                                <i class="fas fa-lock mr-1"></i>Editar estado
                                            </button>
                                            <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 max-w-56">
                                                <p class="text-xs font-bold text-red-700">Motivo:</p>
                                                <p class="text-xs text-red-600 break-words"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    </dialog>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
