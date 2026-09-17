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
                            <tr class="hover:bg-gray-50 transition-colors">
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
                                    <?php if (!empty($p['usos_saldo'])): ?><p class="mt-2 text-xs font-bold text-green-700">Pagado con saldo a favor<br><span class="text-sm">Saldo utilizado: $ <?php echo number_format(array_sum(array_column($p['usos_saldo'], 'monto')), 2, '.', ','); ?></span></p><?php endif; ?>
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
                                    
                                    <dialog class="acciones-pago w-[92vw] max-w-md max-h-[85vh] overflow-y-auto rounded-2xl p-5 backdrop:bg-black/60">
                                    <button type="button" data-cerrar-acciones class="float-right rounded-lg px-3 py-2" aria-label="Cerrar acciones">✕</button>
                                    <h3 class="mb-4 text-xl font-bold">Detalle del saldo</h3>
                                    <div class="flex flex-col gap-2">
                                        <?php foreach ($p['usos_saldo'] ?? [] as $uso): ?>
                                            <p class="rounded-lg bg-green-50 p-3 text-sm font-bold text-green-800">Pago con saldo a favor: $ <?php echo number_format($uso['monto'],2); ?>.</p>
                                        <?php endforeach; ?>
                                        <?php if (!empty($p['usos_saldo'])): ?>
                                            <p class="text-sm font-bold">Diferencia depositada: $ <?php echo number_format(max(0, $p['monto_total'] - array_sum(array_column($p['usos_saldo'],'monto'))),2); ?></p>
                                        <?php endif; ?>
                                        <?php foreach ($p['destinos_saldo'] ?? [] as $uso): ?>
                                            <p class="rounded-lg bg-blue-50 p-3 text-sm">Saldo aplicado al pago #<?php echo (int)$uso['pago_id']; ?>: $ <?php echo number_format($uso['monto'],2); ?></p>
                                        <?php endforeach; ?>
                                        <?php if (!$esAnulado && empty($p['reporte_diferencia']) && in_array($_SESSION['rol'] ?? '', ['admin','secretaria'], true)): ?>
                                            <button type="button" data-registrar-excedente="<?php echo (int)$p['id']; ?>" hidden style="display:none" class="rounded-lg bg-amber-500 p-3 font-bold text-white">Registrar excedente</button>
                                        <?php endif; ?>
                                        <?php if (!empty($p['reporte_diferencia'])):
                                            $reporte = $p['reporte_diferencia']; ?>
                                            <div class="min-w-0 rounded-xl border border-amber-300 bg-amber-50 p-3" data-panel-diferencia="<?php echo (int)$p['id']; ?>">
                                                <p class="text-xs font-extrabold uppercase tracking-wide text-amber-800"><i class="fas fa-scale-unbalanced mr-1"></i>Diferencia reportada</p>
                                                <p class="mt-1 text-sm text-amber-900">Debía pagar <strong>$ <?php echo number_format((float)$p['monto_total'], 2, '.', ','); ?></strong> y reportó <strong>$ <?php echo number_format((float)$reporte['monto_depositado'], 2, '.', ','); ?></strong>.</p>
                                                <label class="mt-2 block text-xs font-bold text-gray-700">Saldo a favor confirmado
                                                    <input data-saldo-diferencia type="number" min="0" step="0.01" value="<?php echo number_format((float)$reporte['saldo_favor'], 2, '.', ''); ?>" class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-2 py-1.5">
                                                </label>
                                                <label class="mt-2 block text-xs font-bold text-gray-700">Nota para el usuario
                                                    <textarea data-nota-diferencia maxlength="255" rows="2" class="mt-1 w-full resize-none rounded-lg border border-amber-300 bg-white px-2 py-1.5"><?php echo htmlspecialchars($reporte['nota_admin'] ?? ''); ?></textarea>
                                                </label>
                                                <button type="button" data-resolver-diferencia="<?php echo (int)$p['id']; ?>" class="mt-2 w-full rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white hover:bg-amber-700"><i class="fas fa-floppy-disk mr-1"></i>Guardar revisión</button>
                                                <p data-estado-diferencia class="mt-1 text-xs font-bold <?php echo $reporte['estado'] === 'confirmado' ? 'text-green-700' : ($reporte['estado'] === 'rechazado' ? 'text-gray-600' : 'text-amber-700'); ?>"><?php echo $reporte['estado'] === 'confirmado' ? 'Saldo confirmado' : ($reporte['estado'] === 'rechazado' ? 'Reporte sin saldo' : 'Pendiente de revisión'); ?></p>
                                            </div>
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
