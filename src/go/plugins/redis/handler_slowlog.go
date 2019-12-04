/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package redis

import (
	"github.com/mediocregopher/radix/v3"
	"reflect"
)

type slowlog []interface{}
type logItem = []interface{}

// getLastSlowlogId gets the last log item ID from slowlog.
func getLastSlowlogId(sl slowlog) (int64, error) {
	if len(sl) == 0 {
		return 0, nil
	}

	if reflect.TypeOf(sl[0]).Kind().String() != "slice" {
		return 0, errorCannotParseData
	}

	item := sl[0].(logItem)

	if len(item) == 0 {
		return 0, errorCannotParseData
	}

	if reflect.TypeOf(item[0]).Name() != "int64" {
		return 0, errorCannotParseData
	}

	return item[0].(int64) + 1, nil
}

// slowlogHandler gets an output of 'SLOWLOG GET 1' command and returns the last slowlog Id.
func (p *Plugin) slowlogHandler(conn redisConn, params []string) (interface{}, error) {
	var res []interface{}

	if err := conn.Do(radix.Cmd(&res, "SLOWLOG", "GET", "1")); err != nil {
		p.Errf(err.Error())
		return nil, errorCannotFetchData
	}

	lastId, err := getLastSlowlogId(slowlog(res))

	return lastId, err
}
