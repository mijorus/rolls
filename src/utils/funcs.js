export function validateUUID(uuid) {
	const REGEX = /^(?:[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}|00000000-0000-0000-0000-000000000000|ffffffff-ffff-ffff-ffff-ffffffffffff)$/i;
	return typeof uuid === 'string' && REGEX.test(uuid);
}

export function randomString(length, chars) {
	chars = chars || "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	var result = "";
	for (var i = length; i > 0; --i) {
		result += chars[Math.floor(Math.random() * chars.length)];
	}
	return result;
}

export function xmlResponse(xml) {
	const p = new DOMParser();
	return p.parseFromString(xml, 'text/xml');
}

export function convertUrlsToMarkdown(text) {
	// Regular expression to match HTTP(S) URLs
	const urlRegex = /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/gi;
	// Replace all URLs with the markdown syntax
	return text.replace(urlRegex, (m) => `[${m}](${m})`);
}

export function escapeXml(s) {
	var XML_CHAR_MAP = {
		'<': '&lt;',
		'>': '&gt;',
		'&': '&amp;',
		'"': '&quot;',
		"'": '&apos;'
	};

	return s.replace(/[<>&"']/g, function (ch) {
		return XML_CHAR_MAP[ch];
	});
}

export function detectMobile() {
    const toMatch = [
        /Android/i,
        /webOS/i,
        /iPhone/i,
        /iPad/i,
        /iPod/i,
        /BlackBerry/i,
        /Windows Phone/i
    ];
    
    return toMatch.some((toMatchItem) => {
        return navigator.userAgent.match(toMatchItem);
    });
}