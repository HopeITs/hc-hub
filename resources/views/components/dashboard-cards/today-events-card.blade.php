<div class="js-today-events w-full h-full max-w-md bg-white rounded-3xl shadow-sm ring-1 ring-gray-200 overflow-hidden relative">
    <div class="flex items-center justify-between px-7 pt-6">
        <div class="flex items-center gap-2">
            <span class="text-[13px] font-medium text-[#58595B]">Today's Events</span>
            <span class="text-[13px] font-light text-secondary-light">6/6/2026</span>
            <img
                src="/assets/icons/birthday-cake.svg"
                alt="Birthday watermark icon"
                class="w-5 h-5 select-none">
        </div>
    </div>

    <div class="swiper">
        <div class="swiper-wrapper">

            <div class="swiper-slide">
                <div class="relative h-[240px] flex flex-col items-center justify-center">
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    </div>

                    <span class="confetti-anchor relative inline-flex">
                        <img
                            src="https://images.unsplash.com/photo-1607746882042-944635dfe10e?q=80&w=320&auto=format&fit=crop"
                            alt="Layla Thompson"
                            class="relative z-10 w-18 h-18 rounded-full object-cover">
                    </span>

                    <div class="mt-2 z-10 text-center">
                        <p class="font-semibold text-[14px] text-[#58595B]">Layla Thompson</p>
                        <p class="text-[9.5px] tracking-wider font-light text-[#58595B]">Marketing Specialist</p>
                        <p class="text-[9px] text-gray-400 mt-1">Happy Birthday! 🎉</p>
                    </div>

                    <div class="pointer-events-none absolute right-0 -bottom-2 opacity-10">
                        <img
                            src="/assets/icons/birthday-cake.svg"
                            alt="Birthday watermark icon"
                            class="w-20 h-20 select-none">
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="relative h-[240px] flex flex-col items-center justify-center">
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    </div>

                    <span class="confetti-anchor relative inline-flex">
                        <img
                            src="https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=320&q=80"
                            alt="Daniel Ortiz"
                            class="relative z-10 w-18 h-18 rounded-full object-cover">

                    </span>

                    <div class="mt-2 z-10 text-center">
                        <p class="font-semibold text-[14px] text-[#58595B]">Daniel Ortiz</p>
                        <p class="text-[9.5px] tracking-wider font-light text-[#58595B]">Project Coordinator</p>
                        <p class="text-[9px] text-gray-400 mt-1">5-Year Work Anniversary 🎊</p>
                    </div>

                    <div class="pointer-events-none absolute right-1 -bottom-3 opacity-10">
                        <img
                            src="/assets/icons/event.svg"
                            alt="Event watermark icon"
                            class="w-20 h-20 select-none">
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="relative h-[240px] flex flex-col items-center justify-center">
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    </div>

                    <span class="confetti-anchor relative inline-flex">
            <img
                src="https://static.addiyar.com/storage/attachments/2069/prmwanes_628145_large.jpg"
                alt="Saint Celebration"
                class="relative z-10 w-18 h-18 rounded-full object-cover">
        </span>

                    <div class="mt-2 z-10 text-center">
                        <p class="font-semibold text-[14px] text-[#58595B]">Feast of St. Maron</p>
                        <p class="text-[9.5px] tracking-wider font-light text-[#58595B]">Community Chapel – 10 AM</p>
                        <p class="text-[9px] text-gray-400 mt-1">Saint’s Day Celebration ✨</p>
                    </div>

                    <div class="pointer-events-none absolute right-1 -bottom-3 opacity-10">
                        <img
                            src="/assets/icons/event.svg"
                            alt="Saint Day watermark icon"
                            class="w-20 h-20 select-none">
                    </div>
                </div>
            </div>

        </div>

        <div class="swiper-pagination !bottom-5"></div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        new Swiper(".js-today-events .swiper", {
            slidesPerView: 1,
            loop: true,
            autoplay: { delay: 4500, disableOnInteraction: false },
            pagination: { el: ".js-today-events .swiper-pagination", clickable: true },
        });
    });
</script>

<style>
    .js-today-events .swiper-pagination-bullet {
        width: 6px;
        height: 6px;
        background: #d1d5db;
        opacity: 1;
    }
    .js-today-events .swiper-pagination-bullet-active {
        background: #6b7280;
    }
</style>
