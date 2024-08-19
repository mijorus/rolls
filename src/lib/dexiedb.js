export const videoDbName = 'NcRoll';
export const dbVersion = 2;
export const videoDbTable = 'currentStream';
export const videoMetaDbTable = 'currentStreamMeta';
export const videoDbSchema = {
	[videoDbTable]: "id, blob",
	[videoMetaDbTable]: 'id, data'
};