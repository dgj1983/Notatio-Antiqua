/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#include <QApplication>
#include <QTranslator>
#include <QLocale>
#include "naprog.h"

int main(int argc, char *argv[])
{
    QApplication a(argc, argv);
    QTranslator NATrans;
    QString locale = QString("NaProg-%1").arg(QLocale::system().name());
    NATrans.load(locale,qApp->applicationDirPath());
    qApp->installTranslator(&NATrans);
    QCoreApplication::setOrganizationName("DGSOFTWARE");
    QCoreApplication::setOrganizationDomain("http://www.dgippner.de");
    QCoreApplication::setApplicationName("Notatio Antiqua");
    QFont appfont;
    appfont.setFamily("Ubuntu");
    QApplication::setFont(appfont);
    NaProg w;
    w.showMaximized();
    return a.exec();
}
