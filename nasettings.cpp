/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#include <QtDebug>
#include <QDesktopServices>
#include "nasettings.h"
#include "ui_nasettings.h"

NASettings::NASettings(QWidget *parent) :
    QDialog(parent),
    ui(new Ui::NASettings)
{
    ui->setupUi(this);
    QSettings* preferences = new QSettings(QSettings::IniFormat,QSettings::UserScope,"DGSOFTWARE", "Notatio Antiqua");
    preferences->beginGroup("Paths");
    ui->latexE->setText(preferences->value("latexPath").toString());
    ui->gregorioE->setText(preferences->value("gregorioPath").toString());
    ui->lilypondE->setText(preferences->value("lilypondPath").toString());
    preferences->endGroup();
    preferences->beginGroup("Font");
    QFont prefFont;
    prefFont.setFamily(preferences->value("standardFont").toString());
    ui->NAfont->setCurrentFont(prefFont);
    preferences->endGroup();
    connect(this,SIGNAL(accepted()),this,SLOT(modifyIni()));
}

NASettings::~NASettings()
{
    delete ui;
}

void NASettings::changeEvent(QEvent *e)
{
    QDialog::changeEvent(e);
    switch (e->type()) {
    case QEvent::LanguageChange:
        ui->retranslateUi(this);
        break;
    default:
        break;
    }
}

void NASettings::modifyIni()
{
    QSettings* preferences = new QSettings(QSettings::IniFormat,QSettings::UserScope,"DGSOFTWARE", "Notatio Antiqua");
    preferences->beginGroup("Paths");
    preferences->setValue("latexPath",ui->latexE->text());
    preferences->setValue("gregorioPath",ui->gregorioE->text());
    preferences->setValue("lilypondPath",ui->lilypondE->text());
    preferences->endGroup();
    preferences->beginGroup("Font");
    QFont saveFont;
    saveFont = ui->NAfont->currentFont();
    preferences->setValue("standardFont",saveFont.family());
    preferences->endGroup();
}



