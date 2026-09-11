                    <?php foreach ($pagables as $card): ?>
                    <article data-id="<?php echo $card['id']; ?>"
                             data-disco="<?php echo htmlspecialchars($card['disco']); ?>"
                             data-fecha="<?php echo htmlspecialchars($card['fechaLegible']); ?>"
                             data-valor="<?php echo $card['valorFmt']; ?>"
                             class="cardPagar snap-center shrink-0 w-[82%] max-w-[340px] md:w-auto md:max-w-none md:shrink min-h-0 bg-gradient-to-br from-blue-500 to-blue-700 rounded-3xl p-7 lg:p-9 shadow-xl text-white cursor-pointer active:scale-95 transition-transform">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-blue-100 text-sm lg:text-base font-bold uppercase tracking-widest"><?php echo $card['hoy'] ? 'Pago de hoy' : 'Pago pendiente'; ?></p>
                                <p class="text-2xl lg:text-3xl font-bold mt-1"><?php echo htmlspecialchars($card['fechaLegible']); ?></p>
                            </div>
                            <span class="bg-white text-blue-700 font-extrabold px-4 py-2 rounded-full text-lg lg:text-xl shadow max-w-full break-all">Disco <?php echo htmlspecialchars($card['disco']); ?></span>
                        </div>
                        <div class="mt-9 lg:mt-11 text-center">
                            <div class="text-7xl lg:text-8xl font-extrabold leading-none">
                                <span class="align-top text-4xl lg:text-5xl">$</span><?php echo $card['valorFmt']; ?>
                            </div>
                        </div>
                        <div class="mt-9 lg:mt-11 flex items-center gap-3 justify-center">
                            <i class="fas fa-route text-2xl"></i>
                            <span class="text-xl lg:text-2xl font-bold truncate"><?php echo htmlspecialchars($card['ruta']); ?></span>
                        </div>
                        <span class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-white py-3 text-lg font-extrabold text-blue-700"><i class="fas fa-money-bill-wave"></i>Pagar</span>
                    </article>
                    <?php endforeach; ?>