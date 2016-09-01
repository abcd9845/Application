# -*- coding: utf-8 -*
import sys
import MySQLdb as mdb
import json
reload(sys)
sys.setdefaultencoding('utf8')

con = None
tmpProvince = None
tmpCity = None
jsonArray = {}
printjsonArray = {}
try:
    con = mdb.connect('115.28.133.234', 'root',
        'abc123', 'farm',charset="utf8");
    cur = con.cursor()
    sqlStr = "SELECT c_provinces.provinceid,c_provinces.province,c_cities.cityid,c_cities.city,c_areas.areaid,c_areas.area from c_provinces"
    sqlStr += " left join c_cities"
    sqlStr += " on c_cities.provinceid = c_provinces.provinceid"
    sqlStr += " left join c_areas"
    sqlStr += " on c_areas.cityid = c_cities.cityid order by c_provinces.provinceid,c_cities.cityid,c_areas.areaid"
    cur.execute(sqlStr);
    data = cur.fetchone()
    rows = cur.fetchall()
    for row in rows:
        if tmpProvince != row[0]:
            jsonArray.setdefault(row[0],{"name": row[1],"citys":{}})
            tmpProvince = row[0]

        if tmpCity != row[2]:
             cityjson = {"name": row[3],"areas":{}}
             jsonArray[row[0]]["citys"].setdefault(row[2],cityjson)
             tmpCity = row[2]

        if row[4]!=None:
            jsonArray[row[0]]["citys"][row[2]]["areas"].setdefault(row[4],row[5])

    #jsonArray = sorted(jsonArray.items(),key=lambda jsonArray:jsonArray[0],reverse=False)

    str = json.dumps(jsonArray,ensure_ascii=False,sort_keys="key")

    print str

    file_object = open('address.js', 'wr')
    file_object.write("var gsfarm_address = "+str)
    file_object.close()
finally:
    if con:
        con.close()