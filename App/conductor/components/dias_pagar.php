                    <?php if (empty($pagables)): ?>
                        <div class="p-8 text-center">
                            <i class="fas fa-circle-check text-green-500 text-4xl mb-3"></i>
                            <p class="text-2xl text-gray-600">No hay pagos pendientes para este disco.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pagables as $dia): ?>
                        <label class="checkDiaFila flex items-center gap-4 p-5 lg:p-6 cursor-pointer hover:bg-blue-50 transition-colors">
                            <input type="checkbox" class="checkDia w-7 h-7 lg:w-8 lg:h-8 accent-blue-600 shrink-0"
                                   value="<?php echo $dia['id']; ?>"
                                   data-valor="<?php echo $dia['valor']; ?>"
                                   data-disco="<?php echo htmlspecialchars($dia['disco']); ?>"
                                   data-fecha="<?php echo htmlspecialchars($dia['fechaLegible']); ?>"
                                   data-valorfmt="<?php echo $dia['valorFmt']; ?>">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 text-xl lg:text-2xl"><?php echo htmlspecialchars($dia['fechaLegible']); ?></p>
                                <p class="text-lg text-gray-500 truncate mt-1">
                                    <i class="fas fa-route mr-1"></i><?php echo htmlspecialchars($dia['ruta']); ?>
                                    <span class="mx-1">·</span>Disco <?php echo htmlspecialchars($dia['disco']); ?>
                                </p>
                            </div>
                            <p class="font-extrabold text-blue-700 text-2xl lg:text-3xl whitespace-nowrap">$ <?php echo $dia['valorFmt']; ?></p>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>