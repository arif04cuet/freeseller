<section class="bg-white dark:bg-gray-900">
    <div class="py-4 px-4  md:px-0">
        <h2 class="mb-2 text-2xl tracking-tight font-extrabold text-gray-900 dark:text-white">
            সচরাচর জিজ্ঞাস্য
        </h2>
        <div class="grid pt-8 text-left border-t border-gray-200 gap-8 dark:border-gray-700 md:grid-cols-2">

            @foreach ($faqs as $faq)
                <x-faq-item :item="$faq" />
            @endforeach


        </div>
    </div>
</section>
