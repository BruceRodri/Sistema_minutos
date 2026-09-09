<?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-receipt text-3xl mb-3 text-gray-300"></i>
                                    <p>No hay pagos que coincidan con los filtros seleccionados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p):
                                $esEspera = $p['estado'] === 'en_espera';
                                $esAnulado = $p['estado'] === 'anulado';
                                $esAprobado = $p['estado'] === 'aprobado';
                                $conductor = trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''));
                                $tieneComprobante = trim((string)($p['comprobante'] ?? '')) !== '';
                                $rutaComprobante = htmlspecialchars('../../Controllers/ComprobanteController.php?pago_id=' . (int)$p['id'] . '&archivo=' . rawurlencode(basename($p['comprobante'] ?: '')), ENT_QUOTES, 'UTF-8');
                                $rutaDescarga = htmlspecialchars('../../Controllers/ComprobanteController.php?pago_id=' . (int)$p['id'] . '&archivo=' . rawurlencode(basename($p['comprobante'] ?: '')) . '&descargar=1', ENT_QUOTES, 'UTF-8');
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
                                    <span class="text-base font-extrabold text-gray-800">$ <?php echo number_format((float)$p['monto_total'], 2, '.', ','); ?></span>
                                </td>
                                <td class="px-5 py-4 min-w-72">
                                    <div class="flex flex-col gap-1.5 mt-2 <?php echo $esAnulado ? 'opacity-50 pointer-events-none' : ''; ?>"
                                         data-pago="<?php echo (int)$p['id']; ?>"
                                         data-codigos="<?php echo htmlspecialchars($p['nro_comprobante'] ?? ''); ?>"
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
                                    <?php if ($esAprobado): ?>
                                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-bold text-green-700">
                                            <i class="fas fa-circle-check mr-1"></i>Aprobado
                                        </span>
                                    <?php elseif ($esAnulado): ?>
                                        <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700">
                                            <i class="fas fa-circle-xmark mr-1"></i>Anulado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                            <i class="fas fa-clock mr-1"></i>En espera
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-2">
                                        <?php if ($tieneComprobante): ?>
                                            <span class="flex gap-1.5">
                                                <a href="<?php echo $rutaComprobante; ?>" target="_blank" rel="noopener"
                                                   title="Abrir comprobante en otra pestaña"
                                                   class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                                                    <i class="fas fa-eye mr-1.5"></i>Ver comprobante
                                                </a>
                                                <a href="<?php echo $rutaDescarga; ?>" target="_blank" rel="noopener"
                                                   title="Descargar comprobante"
                                                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-400 cursor-not-allowed">
                                                <i class="fas fa-eye-slash mr-1.5"></i>Sin comprobante
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($esEspera): ?>
                                            <div class="flex gap-2">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>"
                                                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1.5"></i>Aprobar
                                                </button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>"
                                                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-xmark mr-1.5"></i>Anular
                                                </button>
                                            </div>
                                        <?php elseif ($esAprobado): ?>
                                            <button type="button" data-editar-estado
                                                    class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50 transition-colors">
                                                <i class="fas fa-pen mr-1"></i>Editar estado
                                            </button>
                                            <div data-opciones-estado class="hidden flex flex-wrap gap-2">
                                                <button type="button" data-aprobar="<?php echo (int)$p['id']; ?>" class="rounded-lg bg-green-600 px-3 py-2 text-xs font-bold text-white">Aprobar</button>
                                                <button type="button" data-desaprobar="<?php echo (int)$p['id']; ?>" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">Anular</button>
                                                <button type="button" data-espera="<?php echo (int)$p['id']; ?>" class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-bold text-amber-800">En espera</button>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" data-editar-estado disabled
                                                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-400 cursor-not-allowed">
                                                <i class="fas fa-lock mr-1"></i>Editar estado
                                            </button>
                                            <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 max-w-56">
                                                <p class="text-xs font-bold text-red-700">Motivo:</p>
                                                <p class="text-xs text-red-600 break-words"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>