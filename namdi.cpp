/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#include <QtGui>
#include "namdi.h"

MdiChild::MdiChild()
{
    setAttribute(Qt::WA_DeleteOnClose);
    isUntitled = true;
}

void MdiChild::newFile()
{
    static int sequenceNumber = 1;
    QSettings* preferences = new QSettings(QSettings::IniFormat, QSettings::UserScope,
    "DGSOFTWARE", "Notatio Antiqua");
    preferences->beginGroup("Font");
    if (preferences->value("standardFont") != "")
            setFontFamily(preferences->value("standardFont").toString());
    preferences->endGroup();
    isUntitled = true;
    curFile = tr("Document%1.gabc").arg(sequenceNumber++);
    setWindowTitle(curFile + "[*]");
    highlighter = new Highlighter(document());
    connect(document(), SIGNAL(contentsChanged()),
            this, SLOT(documentWasModified()));
    highlighter = new Highlighter(document());   
}

bool MdiChild::loadFile(const QString &fileName)
{
    QSettings* preferences = new QSettings(QSettings::IniFormat, QSettings::UserScope,
    "DGSOFTWARE", "Notatio Antiqua");
    preferences->beginGroup("Font");
    if (preferences->value("standardFont") != "")
            setFontFamily(preferences->value("standardFont").toString());
    preferences->endGroup();
    QFile file(fileName);
    if (!file.open(QFile::ReadOnly | QFile::Text)) {
        QMessageBox::warning(this, tr("Notatio Antiqua"),
                             tr("Can't read file: %1:\n%2.")
                             .arg(fileName)
                             .arg(file.errorString()));
        return false;
    }
    preferences->beginGroup("File");
    preferences->setValue("lastOpen",fileName);
    preferences->endGroup();
    QTextStream in(&file);
    in.setCodec("UTF-8");
    QApplication::setOverrideCursor(Qt::WaitCursor);
    setPlainText(in.readAll());
    QApplication::restoreOverrideCursor();
    setCurrentFile(fileName);
    highlighter = new Highlighter(document());
    connect(document(), SIGNAL(contentsChanged()),
            this, SLOT(documentWasModified()));

    return true;
}

bool MdiChild::save()
{
    if (isUntitled) {
        return saveAs();
    } else {
        return saveFile(curFile);
    }
}

bool MdiChild::saveAs()
{
    QString fileName = QFileDialog::getSaveFileName(this, tr("Save as"),
                                                    curFile);
    if (fileName.isEmpty())
        return false;

    return saveFile(fileName);
}

bool MdiChild::saveFile(const QString &fileName)
{
    QFile file(fileName);
    if (!file.open(QFile::WriteOnly | QFile::Text)) {
        QMessageBox::warning(this, tr("Notatio Antiqua"),
                             tr("Can't write on file %1:\n%2.")
                             .arg(fileName)
                             .arg(file.errorString()));
        return false;
    }

    QTextStream out(&file);
    out.setCodec("UTF-8");
    QApplication::setOverrideCursor(Qt::WaitCursor);
    out << toPlainText();
    QApplication::restoreOverrideCursor();

    setCurrentFile(fileName);
    return true;
}

QString MdiChild::userFriendlyCurrentFile()
{
    return strippedName(curFile);
}

void MdiChild::closeEvent(QCloseEvent *event)
{
    if (maybeSave()) {
        event->accept();
    }
    else {
        event->ignore();
    }
}

void MdiChild::documentWasModified()
{

    setWindowModified(document()->isModified());
}

bool MdiChild::maybeSave()
{
    if (document()->isModified()) {
        QMessageBox::StandardButton ret;
        ret = QMessageBox::warning(this, tr("Notatio Antiqua"),
                     tr("'%1' has unsaved changes.\n"
                        "Would you like to save these changes?")
                     .arg(userFriendlyCurrentFile()),
                     QMessageBox::Save | QMessageBox::Discard
                     | QMessageBox::Cancel);
        if (ret == QMessageBox::Save)
            return save();
        else if (ret == QMessageBox::Cancel)
            return false;
    }
    return true;
}

void MdiChild::setCurrentFile(const QString &fileName)
{
    curFile = QFileInfo(fileName).canonicalFilePath();
    isUntitled = false;
    document()->setModified(false);
    setWindowModified(false);
    setWindowTitle(userFriendlyCurrentFile() + "[*]");
}

QString MdiChild::strippedName(const QString &fullFileName)
{
    return QFileInfo(fullFileName).fileName();
}

void MdiChild::insertFromMenuWizard(const QString &value)
{
    append(value);
}
