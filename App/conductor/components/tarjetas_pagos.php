                <?php if (empty($pagos)): ?>
                    <div class="lg:col-span-full bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-200">
                        <i class="fas fa-receipt text-gray-300 text-6xl mb-4"></i>
                        <p class="text-2xl text-gray-500">Aún no has realizado ningún pago.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pagos as $p):
                        $estado = $p['estado'] ?? 'aprobado';
                        $esEspera = $estado === 'en_espera';
                        $esAnulado = $estado === 'anulado';
                        $etiquetaEstado = $esAnulado ? 'Anulado' : ($esEspera ? 'En espera…' : 'Aprobado');
                        $etiquetaClase = $esAnulado
                            ? 'bg-red-100 text-red-700'
                            : ($esEspera ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700');
                        $etiquetaIcono = $esAnulado ? 'fa-circle-xmark' : ($esEspera ? 'fa-clock' : 'fa-check-circle');
                        $bordeTarjeta = $esAnulado ? 'border-red-200' : ($esEspera ? 'border-amber-200' : 'border-green-200');
                        $iconoEstado = $esAnulado ? 'text-red-500' : ($esEspera ? 'text-amber-500' : 'text-green-500');
                        $verboPago = $esAnulado ? 'Ingresado el' : ($esEspera ? 'Subido el' : 'Pagado el');
                        $diaClase = $esAnulado ? 'bg-red-600' : ($esEspera ? 'bg-amber-500' : 'bg-green-600');
                    ?>
                    <div class="cardPago bg-white rounded-3xl p-6 lg:p-7 shadow-sm border-2 <?php echo $bordeTarjeta; ?>" data-discos="<?php echo htmlspecialchars(implode(' ', $p['discos'] ?? [])); ?>">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <?php
                            $fechasPagos = !empty($p['fechas']) ? $p['fechas'] : [$p['fecha_pago']];
                            $cantidadDias = count($fechasPagos);
                            if ($cantidadDias > 1):
                                $desde = min($fechasPagos);
                                $hasta = max($fechasPagos);
                                $rango = date('d/m/Y', strtotime($desde));
                                if ($hasta !== $desde) $rango .= ' al ' . date('d/m/Y', strtotime($hasta));
                            ?>
                            <div class="flex flex-col gap-1 min-w-0">
                                <p class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-800 leading-tight break-words">
                                    Se pagaron <span class="inline-block align-middle <?php echo $diaClase; ?> text-white px-3 lg:px-4 py-0.5 rounded-full text-lg lg:text-2xl"><?php echo $cantidadDias; ?> días</span>
                                </p>
                                <p class="text-base lg:text-lg text-gray-500 font-semibold break-words">del <?php echo $rango; ?></p>
                            </div>
                            <?php else: ?>
                            <p class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-gray-800 leading-tight break-words min-w-0">
                                <?php echo formatearFechaPago($fechasPagos[0]); ?>
                            </p>
                            <?php endif; ?>
                            <span class="<?php echo $etiquetaClase; ?> text-sm sm:text-lg font-bold px-3 sm:px-4 py-1.5 rounded-full flex items-center gap-2 shrink-0 whitespace-nowrap">
                                <i class="fas <?php echo $etiquetaIcono; ?>"></i> <?php echo $etiquetaEstado; ?>
                            </span>
                        </div>
                        <p class="text-lg lg:text-xl text-gray-500 mt-1 break-words">
                            <?php if (!empty($p['discos'])): ?>
                            <span class="inline-flex items-center gap-1 mr-2 font-extrabold text-blue-700 text-xl lg:text-2xl"><i class="fas fa-compact-disc"></i>Disco <?php echo htmlspecialchars(implode(' · ', $p['discos'])); ?></span>
                            <?php endif; ?>
                            <span class="font-bold text-gray-600"><?php echo $verboPago; ?> <?php echo formatearFechaPago($p['fecha_pago']); ?></span>
                        </p>
                        <hr class="border-gray-100 my-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-lg lg:text-xl text-gray-500 break-words">
                                <?php echo (int)$p['dias']; ?> día(s) · <span class="font-bold text-gray-700">$ <?php echo number_format((float)$p['monto'], 2, '.', ','); ?></span>
                            </p>
                            <i class="fas <?php echo $esAnulado ? 'fa-circle-xmark' : ($esEspera ? 'fa-clock' : 'fa-check-circle'); ?> <?php echo $iconoEstado; ?> text-4xl lg:text-5xl shrink-0"></i>
                        </div>
                        <?php if (!empty($p['comprobante'])): ?>
                        <button type="button" data-ver-recibo="<?php echo htmlspecialchars('../../Controllers/ComprobanteController.php?pago_id=' . (int)$p['id'] . '&archivo=' . rawurlencode(basename($p['comprobante'])), ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 w-full rounded-xl bg-blue-50 text-blue-700 border border-blue-200 py-3 px-4 text-lg font-bold">
                            <i class="fas fa-eye mr-2"></i>Ver comprobante
                        </button>
                        <?php endif; ?>
                        <?php if ($esAnulado): ?>
                        <div class="mt-3 rounded-xl bg-red-50 border border-red-200 p-4">
                            <p class="font-bold text-red-700 text-lg">Motivo: <span class="font-semibold"><?php echo htmlspecialchars($p['motivo_rechazo'] ?: 'Sin motivo especificado.'); ?></span></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>