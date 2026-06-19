/** Right-to-left scripts (Arabic, Hebrew, Syriac, etc.) */
const RTL_CHAR = /[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

/** Latin letters — predominant LTR signal for mixed chat text */
const LTR_CHAR = /[A-Za-z\u00C0-\u024F]/;

/**
 * Infer CSS `direction` from message content.
 *
 * @param {string|null|undefined} text
 * @returns {'rtl'|'ltr'}
 */
export function resolveTextDirection(text) {
    if (!text || !String(text).trim()) {
        return 'ltr';
    }

    let rtl = 0;
    let ltr = 0;

    for (const char of String(text)) {
        if (RTL_CHAR.test(char)) {
            rtl += 1;
        } else if (LTR_CHAR.test(char)) {
            ltr += 1;
        }
    }

    return rtl > ltr ? 'rtl' : 'ltr';
}

/**
 * Classes that align text to the logical start for the resolved direction.
 *
 * @param {string|null|undefined} text
 * @returns {string}
 */
export function textDirectionClasses(text) {
    return 'text-start [unicode-bidi:plaintext]';
}
