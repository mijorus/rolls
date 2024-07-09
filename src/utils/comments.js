import dayjs from "dayjs";
import { COMMENTS_DAYJS_FORMAT } from "./constants";
import axios from "@nextcloud/axios";
import { DAV_URL, REMOTE_URL } from "../constants";
import { escapeXml, xmlResponse } from "./funcs";

export async function createComment(fileId, message) {
	const user = window.OC.getCurrentUser();
	const body = {
		"actorDisplayName": user.displayName,
		"actorId": user.uid,
		"actorType": "users",
		"creationDateTime": dayjs().format(COMMENTS_DAYJS_FORMAT),
		"objectType": "files",
		"verb": "comment",
		message,
	};

	const response = await axios.post(`${DAV_URL}/comments/files/${fileId}`, body);
	const cl =  response.headers.get('Content-Location');

	return cl.split('/').at(-1);
}

export async function getComments(fileId, commentId = undefined) {
	let url = `${DAV_URL}/comments/files/${fileId}`

	if (typeof commentId !== 'undefined') {
		url += `/${commentId}`
	}

	const commentsResponse = (
		await axios.request({
			method: "PROPFIND",
			url,
		})
	).data;

	const responseData = xmlResponse(commentsResponse);

	let comments = [];
	for (let el of responseData.getElementsByTagName("d:prop")) {
		const v = {};
		for (let tag of el.getElementsByTagName("*")) {
			v[tag.tagName] = tag.innerHTML;
		}

		if ("oc:message" in v) {
			v.fromNow = dayjs(
				v["oc:creationDateTime"],
				COMMENTS_DAYJS_FORMAT
			).fromNow();

			comments.push(v);
		}
	}

	return comments;
}

export async function deleteComment(fileId, commentId) {
	await axios.delete(`${DAV_URL}/comments/files/${fileId}/${commentId}`);
}

export async function editComment(fileId, commentId, message) {
	const response = await axios.request({
		method: 'PROPPATCH', 
		url: `${DAV_URL}/comments/files/${fileId}/${commentId}`,
		data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:message>${escapeXml(message)}</oc:message>
				</d:prop>
			</d:set>
		</d:propertyupdate>`,
		headers: {
			"Content-Type": 'text/plain'
		}
	});

	return response;
}