<template>
    <div class="space-y-6">
        <div
            class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg flex justify-between items-center"
        >
            <div class="flex items-center space-x-3">
                <div
                    class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                >
                    <FlDocumentHeader class="w-6 h-6 text-primary-600" />
                </div>
                <div>
                    <h2
                        class="text-xl font-bold text-gray-900 dark:text-gray-300"
                    >
                        {{ t("cards_section_title") }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        {{ t("cards_section_description") }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formData.data.cards.length }}/10 Cards
                </span>
                <button
                    type="button"
                    @click="addCard"
                    :disabled="formData.data.cards.length >= 10"
                    class="bg-success-600 hover:bg-success-700 disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white px-3 py-1 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2"
                >
                    <span class="text-lg leading-none"><AnOutlinedPlus class="w-4 h-4"/></span>
                    {{ t("add_card") }}
                </button>
            </div>
        </div>

        <!-- Cards List -->
        <div v-if="formData.data.cards.length > 0" class="space-y-6">
            <div
                v-for="(card, cardIndex) in formData.data.cards"
                :key="cardIndex"
                class="border border-gray-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800"
            >
                <!-- Card Header - Accordion Style -->
                <div
                    @click="toggleCard(cardIndex)"
                    :class="[
                        'flex justify-between items-center p-5 cursor-pointer',
                        activeCardIndex === cardIndex
                            ? 'border-b border-gray-200 dark:border-slate-700'
                            : 'border-b border-transparent',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="w-6 h-6 bg-indigo-100 dark:bg-indigo-800/30 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold"
                        >
                            {{ cardIndex + 1 }}
                        </span>
                        <span
                            class="font-medium text-gray-700 dark:text-slate-200"
                        >
                            {{ t("card") }} {{ cardIndex + 1 }}
                        </span>
                    </div>
                    <!-- Collapse/Expand Icon -->
                    <div class="flex items-center gap-2">
                        <svg
                            :class="[
                                'w-5 h-5 text-gray-500 dark:text-gray-400 transform transition-transform duration-200',
                                activeCardIndex === cardIndex
                                    ? 'rotate-180'
                                    : '',
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            ></path>
                        </svg>

                        <button
                            v-if="formData.data.cards.length > 2"
                            type="button"
                            @click.stop="removeCard(cardIndex)"
                            class="text-danger-500 hover:text-danger-700 dark:hover:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20 p-2 rounded-lg transition-all duration-200"
                            title="Remove card"
                        >
                            <BsTrash3 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Card Content - Collapsible -->
                <div
                    v-show="activeCardIndex === cardIndex"
                    class="space-y-6 p-6 transition-all duration-300"
                >
                    <!-- Card Header Section -->
                    <div class="space-y-4">
                        <h3
                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                        >
                            {{ t("header") }}
                        </h3>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
                            >
                                {{ t("header_type") }}
                            </label>
                            <v-select
                                v-model="card.header.type"
                                :options="headerTypeOptions"
                                label="label"
                                :reduce="(option) => option.value"
                                placeholder="Select Header Type"
                                :clearable="false"
                                :searchable="false"
                                class="vue-select-custom dark:vue-select-dark"
                            />
                        </div>

                        <!-- Media Upload -->
                        <div
                            v-if="['IMAGE', 'VIDEO'].includes(card.header.type)"
                            class="space-y-4"
                        >
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
                            >
                                Upload {{ card.header.type.toLowerCase() }}
                            </label>

                            <!-- File Upload Area -->
                            <div
                                @dragover.prevent
                                @dragenter.prevent
                                @dragleave="card.isDragOver = false"
                                @drop="(e) => handleCardFileDrop(e, cardIndex)"
                                @click="() => triggerCardFileInput(cardIndex)"
                                :class="[
                                    'relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200',
                                    card.isDragOver
                                        ? 'border-purple-400 dark:border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20',
                                ]"
                            >
                                <input
                                    :data-card-file-input="cardIndex"
                                    type="file"
                                    :accept="
                                        card.header.type === 'IMAGE'
                                            ? 'image/*'
                                            : 'video/*'
                                    "
                                    @change="
                                        (e) =>
                                            handleCardFileSelect(e, cardIndex)
                                    "
                                    class="hidden"
                                />

                                <!-- Upload Icon and Text -->
                                <div
                                    v-if="
                                        !card.uploadedFile &&
                                        !card.header.media_url
                                    "
                                    class="space-y-3"
                                >
                                    <div class="flex justify-center">
                                        <div
                                            class="w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-full flex items-center justify-center"
                                        >
                                            <svg
                                                class="w-5 h-5 text-purple-600 dark:text-purple-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                ></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            {{ t("file_drop_text") }}
                                            {{ card.header.type.toLowerCase() }}
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                        >
                                            {{
                                                card.header.type === "IMAGE"
                                                    ? "JPG, PNG, GIF, WebP (Max 5MB)"
                                                    : "MP4, MOV, AVI (Max 16MB)"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- File Preview -->
                                <div v-else class="space-y-2">
                                    <div
                                        class="flex items-center justify-center space-x-2"
                                    >
                                        <div
                                            class="w-8 h-8 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center"
                                        >
                                            <svg
                                                class="w-4 h-4 text-green-600 dark:text-green-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                ></path>
                                            </svg>
                                        </div>
                                        <p
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                card.uploadedFile
                                                    ? card.uploadedFile.name
                                                    : "File uploaded"
                                            }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click.stop="
                                            () => removeCardFile(cardIndex)
                                        "
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium"
                                    >
                                        {{ t("remove_file") }}
                                    </button>
                                </div>
                            </div>

                            <!-- URL Input Alternative -->
                            <div
                                class="border-t border-gray-200 dark:border-gray-700 pt-4"
                            >
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    {{ t("or_provide_url_directly") }}
                                </label>
                                <input
                                    v-model="card.header.media_url"
                                    type="url"
                                    :placeholder="
                                        t('enter_media_url_placeholder')
                                    "
                                    class="block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Card Body Section -->
                    <div class="space-y-6">
                        <div
                            class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg"
                        >
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                >
                                    <HeOutlineBody
                                        class="w-6 h-6 text-primary-600"
                                    />
                                </div>
                                <div>
                                    <h2
                                        class="text-xl font-bold text-gray-900 dark:text-gray-300"
                                    >
                                        {{ t("message_body_title") }}
                                    </h2>
                                    <p
                                        class="text-sm text-gray-500 dark:text-gray-300"
                                    >
                                        {{ t("message_body_description") }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div
                                    class="flex items-center justify-between mb-3"
                                >
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{ t("message_body_content") }}
                                        <span class="text-danger-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="addCardBodyVariable(cardIndex)"
                                        class="bg-success-600 hover:bg-success-700 text-white px-3 py-1 rounded-md text-xs font-medium transition-all duration-200 flex items-center gap-1"
                                    >
                                        <span class="text-sm"><AnOutlinedPlus class="w-4 h-4"/></span>
                                        {{ t("add_variable") }}
                                    </button>
                                </div>

                                <!-- Rich Text Formatting Toolbar -->
                                <div
                                    class="border border-info-300 dark:border-info-700 rounded-lg bg-info-50 dark:bg-info-900/10 p-2 flex flex-wrap items-center gap-1"
                                >
                                    <!-- Bold -->
                                    <button
                                        type="button"
                                        @click="
                                            applyCardFormatting(
                                                'bold',
                                                cardIndex,
                                            )
                                        "
                                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                        title="Bold"
                                    >
                                        <span class="font-bold">B</span>
                                    </button>

                                    <!-- Italic -->
                                    <button
                                        type="button"
                                        @click="
                                            applyCardFormatting(
                                                'italic',
                                                cardIndex,
                                            )
                                        "
                                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                        title="Italic"
                                    >
                                        <span class="italic">I</span>
                                    </button>

                                    <!-- Strikethrough -->
                                    <button
                                        type="button"
                                        @click="
                                            applyCardFormatting(
                                                'strikethrough',
                                                cardIndex,
                                            )
                                        "
                                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                        title="Strikethrough"
                                    >
                                        <span class="line-through">S</span>
                                    </button>

                                    <!-- Code -->
                                    <button
                                        type="button"
                                        @click="
                                            applyCardFormatting(
                                                'code',
                                                cardIndex,
                                            )
                                        "
                                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                        title="Code"
                                    >
                                        <span
                                            class="font-mono bg-gray-200 dark:bg-slate-600 px-1 rounded"
                                        >
                                            &lt;/&gt;
                                        </span>
                                    </button>

                                    <!-- Helper text -->
                                    <div
                                        class="hidden sm:block ml-2 text-xs text-gray-500 dark:text-slate-400"
                                    >
                                        {{ t("text_formatting_help") }}
                                    </div>
                                </div>

                                <textarea
                                    :ref="
                                        (el) =>
                                            setCardBodyTextarea(el, cardIndex)
                                    "
                                    v-model="card.body"
                                    required
                                    rows="6"
                                    maxlength="1024"
                                    placeholder="Enter your message body. Select text and use formatting buttons above."
                                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:bg-slate-100 disabled:cursor-wait dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                ></textarea>
                                <div
                                    class="flex justify-between items-center mt-2"
                                >
                                    <p class="text-xs text-gray-500">
                                        {{ (card.body || "").length }}/1024
                                        {{ t("characters") }}
                                    </p>
                                    <span
                                        class="text-xs text-gray-400 hidden sm:inline"
                                    >
                                        Variables: {{ 1 }}, {{ 2 }}, etc. |
                                        Formatting: *bold*, _italic_,
                                        ~strikethrough~, ```code```
                                    </span>
                                </div>
                            </div>

                            <!-- Placeholder Helper -->
                            <div
                                v-if="getCardPlaceholders(cardIndex).length > 0"
                                class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-700 rounded-lg p-4"
                            >
                                <div class="flex items-center gap-2 mb-3">
                                    <div
                                        class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >?</span
                                        >
                                    </div>
                                    <p
                                        class="text-sm font-medium text-blue-900 dark:text-blue-200"
                                    >
                                        {{ t("detected_placeholders") }}
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <div
                                        v-for="(
                                            placeholder, placeholderIndex
                                        ) in getCardPlaceholders(cardIndex)"
                                        :key="`${cardIndex}-${placeholder}`"
                                        class="flex flex-col sm:flex-row items-start sm:items-center gap-3"
                                    >
                                        <span
                                            class="text-sm font-mono text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded whitespace-nowrap"
                                        >
                                            {{ formatPlaceholder(placeholder) }}
                                        </span>
                                        <input
                                            :value="
                                                getCardPreviewValue(
                                                    cardIndex,
                                                    placeholderIndex,
                                                )
                                            "
                                            @input="
                                                updateCardPreviewValue(
                                                    cardIndex,
                                                    placeholderIndex,
                                                    $event.target.value,
                                                )
                                            "
                                            type="text"
                                            :placeholder="`Preview value for {{${placeholder}}}`"
                                            class="flex-1 w-full sm:w-auto text-sm border border-blue-200 dark:border-blue-700 bg-white dark:bg-blue-950 text-blue-900 dark:text-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Buttons Section -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3
                                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ t("buttons") }}
                            </h3>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ card.buttons.length }}/2 Buttons
                                </span>
                                <button
                                    type="button"
                                    @click="() => addCardButton(cardIndex)"
                                    :disabled="card.buttons.length >= 2"
                                    class="bg-success-600 hover:bg-success-700 disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white px-2 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1"
                                >
                                    <span class="text-sm"><AnOutlinedPlus class="w-4 h-4"/></span>
                                    {{ t("add_button") }}
                                </button>
                            </div>
                        </div>

                        <!-- Buttons List -->
                        <div v-if="card.buttons.length > 0" class="space-y-3">
                            <div
                                v-for="(button, buttonIndex) in card.buttons"
                                :key="buttonIndex"
                                class="border border-gray-200 dark:border-slate-600 rounded-lg p-3 bg-gray-50 dark:bg-slate-700"
                            >
                                <div
                                    class="flex justify-between items-center mb-3"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-slate-200"
                                    >
                                        {{ t("button") }} {{ buttonIndex + 1 }}
                                    </span>
                                    <button
                                        v-if="card.buttons.length > 1"
                                        type="button"
                                        @click="
                                            () =>
                                                removeCardButton(
                                                    cardIndex,
                                                    buttonIndex,
                                                )
                                        "
                                        class="text-danger-500 hover:text-danger-700 dark:hover:text-danger-400 p-1 rounded transition-all duration-200"
                                        title="Remove button"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <!-- Button Type -->
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1"
                                        >
                                            {{ t("button_type") }}
                                        </label>
                                        <v-select
                                            v-model="button.type"
                                            :options="buttonTypeOptions"
                                            label="label"
                                            :reduce="(option) => option.value"
                                            placeholder="Select Button Type"
                                            :clearable="false"
                                            :searchable="false"
                                            class="vue-select-custom"
                                        />
                                    </div>

                                    <!-- Button Text -->
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1"
                                        >
                                            {{ t("button_text") }}
                                            <span class="text-danger-500"
                                                >*</span
                                            >
                                        </label>
                                        <input
                                            v-model="button.text"
                                            type="text"
                                            required
                                            maxlength="20"
                                            placeholder="Enter button text"
                                            class="block w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500"
                                        />
                                    </div>

                                    <!-- Website URL -->
                                    <div v-if="button.type === 'URL'">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1"
                                        >
                                            {{ t("website_url") }}
                                        </label>
                                        <input
                                            v-model="button.url"
                                            type="url"
                                            placeholder="https://example.com"
                                            class="block w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500"
                                        />
                                    </div>

                                    <!-- Phone Number -->
                                    <div v-if="button.type === 'PHONE_NUMBER'">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1"
                                        >
                                            {{ t("phone_number") }}
                                        </label>
                                        <input
                                            v-model="button.phone_number"
                                            type="tel"
                                            placeholder="+1234567890"
                                            class="block w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Button Requirement Notice -->
                        <div
                            v-else
                            class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg"
                        >
                            <p
                                class="text-sm text-yellow-800 dark:text-yellow-200"
                            >
                                {{ t("at_least_one_button_required") }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="text-center p-8 bg-gray-50 dark:bg-slate-700 rounded-lg border-2 border-dashed border-gray-300 dark:border-slate-600"
        >
            <div
                class="w-12 h-12 bg-gray-200 dark:bg-slate-600 rounded-full flex items-center justify-center mx-auto mb-4"
            >
                <svg
                    class="w-6 h-6 text-gray-400 dark:text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    ></path>
                </svg>
            </div>
            <h3
                class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2"
            >
                {{ t("no_cards_added") }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ t("add_your_first_card_to_get_started") }}
            </p>
            <button
                type="button"
                @click="addCard"
                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2 mx-auto"
            >
                <span class="text-lg leading-none">+</span>
                {{ t("add_first_card") }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from "vue";
import {
    FlDocumentHeader,
    HeOutlineBody,
    BsTrash3,
    AnOutlinedPlus
} from "@kalimahapps/vue-icons";

import { useTranslations } from "../../../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();

// Props
const props = defineProps({
    formData: {
        type: Object,
        required: true,
    },
    activeCardIndex: {
        type: Number,
        required: true,
    },
    cardPreviewValues: {
        type: Object,
        default: () => ({}),
    },
});

// Emits
const emit = defineEmits([
    "update:activeCardIndex",
    "update:cardPreviewValues",
]);

// Template refs for card body textareas
const cardBodyTextareas = ref({});

// Computed properties
const headerTypeOptions = computed(() => {
    return [
        { value: "IMAGE", label: `🖼️ ${t("image")}` },
        { value: "VIDEO", label: `🎥 ${t("video")}` },
    ];
});

const buttonTypeOptions = computed(() => {
    return [
        { value: "QUICK_REPLY", label: `💬 ${t("quick_reply")}` },
        { value: "URL", label: `🌐 ${t("website_url")}` },
        { value: "PHONE_NUMBER", label: `📞 ${t("phone_number")}` },
    ];
});

// Computed property for detecting placeholders in each card
const getCardPlaceholders = (cardIndex) => {
    const cardBody = props.formData.data.cards[cardIndex]?.body || "";
    const matches = cardBody.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
};

// Methods
const setCardBodyTextarea = (el, cardIndex) => {
    if (el) {
        cardBodyTextareas.value[cardIndex] = el;
    }
};

const addCard = () => {
    if (props.formData.data.cards.length < 10) {
        const newCardIndex = props.formData.data.cards.length;
        props.formData.data.cards.push({
            header: {
                type: "IMAGE",
                media_url: "",
            },
            body: "",
            buttons: [
                {
                    type: "QUICK_REPLY",
                    text: "",
                    url: "",
                    phone_number: "",
                },
            ],
            // File upload states for each card
            uploadedFile: null,
            isDragOver: false,
            uploadProgress: 0,
            fileError: "",
        });
        // Set the new card as active (open accordion)
        emit("update:activeCardIndex", newCardIndex);

        // Initialize preview values for the new card
        const updatedPreviewValues = { ...props.cardPreviewValues };
        if (!updatedPreviewValues[newCardIndex]) {
            updatedPreviewValues[newCardIndex] = [];
        }
        emit("update:cardPreviewValues", updatedPreviewValues);
    }
};

const toggleCard = (cardIndex) => {
    // Toggle accordion - if same card clicked, close it, otherwise open the clicked card
    const newIndex = props.activeCardIndex === cardIndex ? -1 : cardIndex;
    emit("update:activeCardIndex", newIndex);
};

const removeCard = (cardIndex) => {
    props.formData.data.cards.splice(cardIndex, 1);

    // Clean up preview values for removed card and adjust indexes
    const updatedPreviewValues = { ...props.cardPreviewValues };
    delete updatedPreviewValues[cardIndex];
    delete cardBodyTextareas.value[cardIndex];

    // Reindex preview values and textareas
    const newCardPreviewValues = {};
    const newCardBodyTextareas = {};
    Object.keys(updatedPreviewValues).forEach((key) => {
        const index = parseInt(key);
        if (index > cardIndex) {
            newCardPreviewValues[index - 1] = updatedPreviewValues[key];
        } else if (index < cardIndex) {
            newCardPreviewValues[index] = updatedPreviewValues[key];
        }
    });
    Object.keys(cardBodyTextareas.value).forEach((key) => {
        const index = parseInt(key);
        if (index > cardIndex) {
            newCardBodyTextareas[index - 1] = cardBodyTextareas.value[key];
        } else if (index < cardIndex) {
            newCardBodyTextareas[index] = cardBodyTextareas.value[key];
        }
    });
    emit("update:cardPreviewValues", newCardPreviewValues);
    cardBodyTextareas.value = newCardBodyTextareas;

    // Adjust activeCardIndex after removal
    let newActiveIndex;
    if (props.activeCardIndex === cardIndex) {
        // If removing the active card, set to first card or -1 if no cards
        newActiveIndex = props.formData.data.cards.length > 0 ? 0 : -1;
    } else if (props.activeCardIndex > cardIndex) {
        // If removing a card before the active one, decrement activeCardIndex
        newActiveIndex = props.activeCardIndex - 1;
    } else {
        newActiveIndex = props.activeCardIndex;
    }
    emit("update:activeCardIndex", newActiveIndex);
};

const addCardButton = (cardIndex) => {
    const card = props.formData.data.cards[cardIndex];
    if (card && card.buttons.length < 2) {
        card.buttons.push({
            type: "QUICK_REPLY",
            text: "",
            url: "",
            phone_number: "",
        });
    }
};

const removeCardButton = (cardIndex, buttonIndex) => {
    const card = props.formData.data.cards[cardIndex];
    if (card && card.buttons.length > 1) {
        // Ensure at least 1 button remains
        card.buttons.splice(buttonIndex, 1);
    }
};

// Card file upload methods
const triggerCardFileInput = (cardIndex) => {
    const input = document.querySelector(
        `[data-card-file-input="${cardIndex}"]`,
    );
    if (input) {
        input.click();
    }
};

const handleCardFileDrop = (e, cardIndex) => {
    e.preventDefault();
    const card = props.formData.data.cards[cardIndex];
    card.isDragOver = false;

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleCardFileUpload(files[0], cardIndex);
    }
};

const handleCardFileSelect = (e, cardIndex) => {
    const files = e.target.files;
    if (files.length > 0) {
        handleCardFileUpload(files[0], cardIndex);
    }
};

const handleCardFileUpload = async (file, cardIndex) => {
    const card = props.formData.data.cards[cardIndex];
    card.fileError = "";

    const validationError = validateCardFile(file, card.header.type);
    if (validationError) {
        card.fileError = validationError;
        return;
    }

    card.uploadedFile = file;
    card.uploadProgress = 0;

    // Create preview URL for immediate display
    card.header.media_url = URL.createObjectURL(file);
};

const validateCardFile = (file, headerType) => {
    const maxSizes = {
        IMAGE: 5 * 1024 * 1024, // 5MB
        VIDEO: 16 * 1024 * 1024, // 16MB
    };

    const allowedTypes = {
        IMAGE: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        VIDEO: ["video/mp4", "video/quicktime", "video/x-msvideo"],
    };

    const maxSize = maxSizes[headerType];
    const allowedFileTypes = allowedTypes[headerType];

    if (file.size > maxSize) {
        return `File size exceeds ${formatFileSize(maxSize)} limit`;
    }

    if (!allowedFileTypes.includes(file.type)) {
        return `File type not supported for ${headerType.toLowerCase()}`;
    }

    return null;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const removeCardFile = (cardIndex) => {
    const card = props.formData.data.cards[cardIndex];
    card.uploadedFile = null;
    card.uploadProgress = 0;
    card.header.media_url = "";
    card.fileError = "";
};

// Rich text formatting for card body
const applyCardFormatting = (type, cardIndex) => {
    const textarea = cardBodyTextareas.value[cardIndex];
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const content = props.formData.data.cards[cardIndex].body;

    if (!selectedText) return;

    let formattedText;
    switch (type) {
        case "bold":
            formattedText = `*${selectedText}*`;
            break;
        case "italic":
            formattedText = `_${selectedText}_`;
            break;
        case "strikethrough":
            formattedText = `~${selectedText}~`;
            break;
        case "code":
            formattedText = `\`\`\`${selectedText}\`\`\``;
            break;
    }

    // Replace selected text with formatted text
    const beforeText = content.substring(0, start);
    const afterText = content.substring(end);

    props.formData.data.cards[cardIndex].body =
        beforeText + formattedText + afterText;

    // Set cursor position after formatting
    nextTick(() => {
        const newCursorPos = start + formattedText.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    });
};

const addCardBodyVariable = (cardIndex) => {
    const textarea = cardBodyTextareas.value[cardIndex];
    if (!textarea) return;

    const existingPlaceholders = getCardPlaceholders(cardIndex);
    const nextVariableNum = existingPlaceholders.length + 1;
    const variableToAdd = `{{${nextVariableNum}}}`;

    // Get current cursor position BEFORE any changes
    const startPos = textarea.selectionStart || 0;
    const endPos = textarea.selectionEnd || 0;
    const currentText = textarea.value || "";

    // Split text at cursor position
    const beforeCursor = currentText.substring(0, startPos);
    const afterCursor = currentText.substring(endPos);

    // Create new text with variable inserted
    const newText = beforeCursor + variableToAdd + afterCursor;

    // Update the reactive form data
    props.formData.data.cards[cardIndex].body = newText;

    // Initialize preview values for this card if not exists
    const updatedPreviewValues = { ...props.cardPreviewValues };
    if (!updatedPreviewValues[cardIndex]) {
        updatedPreviewValues[cardIndex] = [];
    }

    // Use nextTick to ensure DOM is updated, then set cursor position
    nextTick(() => {
        const newCursorPos = startPos + variableToAdd.length;
        textarea.value = newText; // Ensure textarea has the new value
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();

        // Update preview values array
        const updatedPlaceholders = getCardPlaceholders(cardIndex);
        while (
            updatedPreviewValues[cardIndex].length < updatedPlaceholders.length
        ) {
            updatedPreviewValues[cardIndex].push(
                `Card ${cardIndex + 1} Value ${updatedPreviewValues[cardIndex].length + 1}`,
            );
        }
        emit("update:cardPreviewValues", updatedPreviewValues);
    });
};

// Helper methods for card preview values
const formatPlaceholder = (placeholder) => {
    return `{{${placeholder}}}`;
};

const getCardPreviewValue = (cardIndex, placeholderIndex) => {
    return props.cardPreviewValues[cardIndex]?.[placeholderIndex] || "";
};

const updateCardPreviewValue = (cardIndex, placeholderIndex, value) => {
    const updatedValues = { ...props.cardPreviewValues };

    if (!updatedValues[cardIndex]) {
        updatedValues[cardIndex] = [];
    }

    // Ensure the array is long enough
    while (updatedValues[cardIndex].length <= placeholderIndex) {
        updatedValues[cardIndex].push("");
    }

    updatedValues[cardIndex][placeholderIndex] = value;
    emit("update:cardPreviewValues", updatedValues);
};
</script>
