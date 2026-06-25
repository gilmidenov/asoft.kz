<template>
    <footer class="bg-header text-white mt-16">
        <div class="container mx-auto px-4 py-10">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center font-bold">A</div>
                        <span class="font-bold">Atlas Software</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">Интернет-магазин лицензионного программного обеспечения в Казахстане</p>
                    <p class="text-gray-400 text-sm mt-3">+7 (707) 597-37-77</p>
                    <p class="text-gray-400 text-sm">info@asoft.kz</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Каталог</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><RouterLink to="/catalog" class="hover:text-white transition-colors">Все программы</RouterLink></li>
                        <li><RouterLink :to="{ name: 'catalog', query: { is_hit: 1 } }" class="hover:text-white transition-colors">Хиты продаж</RouterLink></li>
                        <li><RouterLink :to="{ name: 'catalog', query: { is_new: 1 } }" class="hover:text-white transition-colors">Новинки</RouterLink></li>
                        <li><RouterLink to="/vendors" class="hover:text-white transition-colors">Вендоры</RouterLink></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Помощь</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Как активировать ключ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Способы оплаты</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Возврат и обмен</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Контакты</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Адрес</h4>
                    <p class="text-gray-400 text-sm">Астана, пр.Мангилик Ел, 38.</p>
                    <p class="text-gray-400 text-sm mt-4 font-medium">Время работы:</p>
                    <p class="text-gray-400 text-sm">Пн-Пт: 9:00 – 18:00</p>
                    <p class="text-gray-400 text-sm">Сб: 10:00 – 15:00</p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-sm">© {{ new Date().getFullYear() }} Atlas Software. Все права защищены.</p>
                <p class="text-gray-600 text-xs">asoft.kz — официальные лицензии ПО в Казахстане</p>
            </div>
        </div>
    </footer>
</template>
