<div x-data="{ expanded: false }" class="py-2">
    <h2>
        <button id="faqs-title-03" type="button"
            class="flex items-center justify-between w-full text-left font-semibold py-2" @click="expanded = !expanded"
            :aria-expanded="expanded" aria-controls="faqs-text-03">
            <span class="hover:text-blue-700 font-semibold">{{ $item->question }}</span>
            <svg class="fill-indigo-500 shrink-0 ml-8" width="16" height="16" xmlns="http://www.w3.org/2000/svg">
                <rect y="7" width="16" height="2" rx="1"
                    class="transform origin-center transition duration-200 ease-out"
                    :class="{ '!rotate-180': expanded }" />
                <rect y="7" width="16" height="2" rx="1"
                    class="transform origin-center rotate-90 transition duration-200 ease-out"
                    :class="{ '!rotate-180': expanded }" />
            </svg>
        </button>
    </h2>
    <div id="faqs-text-03" role="region" aria-labelledby="faqs-title-03"
        class="grid  text-slate-600 overflow-hidden transition-all duration-300 ease-in-out pl-2"
        :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
        <div class="overflow-hidden">
            <p class="pb-3 text-justify">
                {!! $item->answer !!}
            </p>
        </div>
    </div>
</div>
