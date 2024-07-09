export const mixins = {
	detectSubmitOnType(event, callback) {
		if(event.key === "Enter" && (event.metaKey || event.ctrlKey)) {
			callback(event)
		}
	}
}