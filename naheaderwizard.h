#ifndef NAHEADERWIZARD_H
#define NAHEADERWIZARD_H

#include <QWizard>


namespace Ui {
    class NAHeaderWizard;
}

class NAHeaderWizard : public QWizard
{
    Q_OBJECT

public:
    explicit NAHeaderWizard(QWidget *parent = 0);
    ~NAHeaderWizard();
    QStringList header;


private slots:
    void accept();

private:
    Ui::NAHeaderWizard *ui;
};

#endif // NAHEADERWIZARD_H
