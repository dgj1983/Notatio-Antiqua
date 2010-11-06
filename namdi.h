/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#ifndef MDIFENSTER_H
#define MDIFENSTER_H

#include <QTextEdit>
#include <QSettings>
#include "nasyntax.h"
#include "naprog.h"

class MdiChild : public QTextEdit
{
    Q_OBJECT

public:
    MdiChild();
    void newFile();
    bool loadFile(const QString &fileName);
    bool save();
    bool saveAs();
    bool saveFile(const QString &fileName);
    QString userFriendlyCurrentFile();
    QString currentFile() { return curFile; }
    bool maybeSave();
    void setCurrentFile(const QString &fileName);
    QString strippedName(const QString &fullFileName);
    QString curFile;
    bool isUntitled;
    Highlighter *highlighter;

protected:
    void closeEvent(QCloseEvent *event);

private slots:
    void documentWasModified();

private:

};

#endif // MDIFENSTER_H
